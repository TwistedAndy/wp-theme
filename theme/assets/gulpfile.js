let fs = require('fs'),
	gulp = require('gulp'),
	sass = require('gulp-sass'),
	notify = require('gulp-notify'),
	concat = require('gulp-concat'),
	rename = require('gulp-rename'),
	plumber = require('gulp-plumber'),
	imagemin = require('gulp-imagemin'),
	globalize = require('gulp-sass-glob'),
	sourcemaps = require('gulp-sourcemaps'),
	inject = require('gulp-inject-string'),
	uglify = require('gulp-uglify-es').default;

if (typeof sass.compiler === 'undefined') {
	sass = sass(require('node-sass'));
}

let folders = {
	build: './build',
	styles: './styles',
	scripts: './scripts',
	images: './images'
};

let sources = {
	style: 'styles/style.scss',
	styles: [
		'styles/*.scss',
		'styles/woo/*.scss',
		'styles/base/*.scss',
		'styles/elements/*.scss',
		'styles/includes/*.scss',
	],
	plugins: 'plugins/**/*.scss',
	preview: 'styles/preview.scss',
	blocks: 'styles/blocks/*.scss',
	scripts: 'scripts/*.js'
};

let options = {
	plumber: {
		errorHandler: notify.onError({
			message: "<%= error.message %>",
			sound: true
		}) || null
	},
	sass: {
		outputStyle: 'compressed',
		indentType: 'tab',
		indentWidth: 1
	},
	sourcemaps: {
		styles: {
			includeContent: false,
			sourceRoot: '../styles/'
		},
		scripts: {
			includeContent: false,
			sourceRoot: '../scripts/'
		}
	}
};

function styles() {
	return gulp.src(sources.style)
		.pipe(plumber(options.plumber))
		.pipe(sourcemaps.init())
		.pipe(globalize())
		.pipe(sass(options.sass))
		.pipe(sourcemaps.write('./', options.sourcemaps.styles))
		.pipe(gulp.dest(folders.build));
}

function scripts() {
	return gulp.src(sources.scripts)
		.pipe(plumber(options.plumber))
		.pipe(sourcemaps.init())
		.pipe(concat('scripts.js'))
		.pipe(uglify())
		.pipe(sourcemaps.write('./', options.sourcemaps.scripts))
		.pipe(gulp.dest(folders.build));
}

function plugins() {
	return gulp.src(sources.plugins, {
			base: './',
			since: gulp.lastRun(plugins)}
		)
		.pipe(plumber(options.plumber))
		.pipe(sourcemaps.init())
		.pipe(sass(options.sass))
		.pipe(sourcemaps.write('./', options.sourcemaps.styles))
		.pipe(gulp.dest('./'));
}

function blocks() {

	let files = fs.readdirSync('./styles/elements/'),
		folder = '../elements/',
		strings = [
			"@import '../includes/variables';",
			"@import '../includes/mixins';"
		],
		exclude = [
			'content.scss',
			'carousel.scss',
			'select2.scss',
		]

	files.forEach(function(file) {
		if (exclude.indexOf(file) === -1) {
			strings.push("@import '" + folder + file + "';");
		}
	});

	return gulp.src(sources.blocks, {
			base: './styles/blocks',
			since: gulp.lastRun(blocks)
		})
		.pipe(plumber(options.plumber))
		.pipe(inject.prepend(strings.join("\n") + "\n"))
		.pipe(globalize())
		.pipe(sass(options.sass))
		.pipe(rename({
			prefix: 'block_'
		}))
		.pipe(gulp.dest(folders.build));
}

function preview() {
	return gulp.src(sources.preview)
		.pipe(plumber(options.plumber))
		.pipe(sourcemaps.init())
		.pipe(globalize())
		.pipe(sass(options.sass))
		.pipe(sourcemaps.write('./', options.sourcemaps.styles))
		.pipe(gulp.dest(folders.build));
}

function images() {
	return gulp.src(folders.images + '/**/*.{png,gif,jpg,jpeg,svg}')
		.pipe(plumber(options.plumber))
		.pipe(imagemin())
		.pipe(gulp.dest(function(file) {
			return file.base;
		}));
}

exports.scripts = scripts;

exports.styles = styles;

exports.blocks = blocks;

exports.plugins = plugins;

exports.preview = preview;

exports.imagemin = images;

exports.default = function() {
	gulp.watch(sources.styles, gulp.parallel(styles, blocks, preview, plugins));
	gulp.watch(sources.scripts, gulp.parallel(scripts));
	gulp.watch(sources.blocks, gulp.parallel(blocks));
	gulp.watch(sources.plugins, gulp.parallel(plugins));
	gulp.watch(sources.preview, gulp.parallel(preview));
}