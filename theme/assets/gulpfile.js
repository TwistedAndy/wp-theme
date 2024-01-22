let gulp = require('gulp'),
	sass = require('gulp-sass'),
	notify = require('gulp-notify'),
	concat = require('gulp-concat'),
	plumber = require('gulp-plumber'),
	imagemin = require('gulp-imagemin'),
	globalize = require('gulp-sass-glob'),
	sourcemaps = require('gulp-sourcemaps'),
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
		'styles/blocks/*.scss',
		'styles/elements/*.scss',
		'styles/includes/*.scss',
	],
	blocks: 'styles/blocks.scss',
	plugins: [
		'plugins/**/*.scss',
	],
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
	return gulp.src(sources.plugins, {base: './'})
		.pipe(plumber(options.plumber))
		.pipe(sourcemaps.init())
		.pipe(sass(options.sass))
		.pipe(sourcemaps.write('./', options.sourcemaps.styles))
		.pipe(gulp.dest('./'));
}

function blocks() {
	return gulp.src(sources.blocks)
		.pipe(plumber(options.plumber))
		.pipe(globalize())
		.pipe(sass(options.sass))
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

exports.styles = styles;

exports.scripts = scripts;

exports.imagemin = images;

exports.blocks = blocks;

exports.plugins = plugins;

exports.default = function() {
	gulp.watch(sources.styles, gulp.parallel(styles));
	gulp.watch(sources.scripts, gulp.parallel(scripts));
	gulp.watch(sources.plugins, gulp.parallel(plugins));
}