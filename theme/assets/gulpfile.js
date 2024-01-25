let fs = require('fs'),
	gulp = require('gulp'),
	sass = require('gulp-sass'),
	notify = require('gulp-notify'),
	concat = require('gulp-concat'),
	replace = require('gulp-replace'),
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
	images: './images',
	blocks: './build/blocks'
};

let sources = {
	woo: 'styles/woo.scss',
	theme: 'styles/theme.scss',
	blocks: 'styles/blocks/*.scss',
	preview: 'styles/preview.scss',
	plugins: 'plugins/**/*.scss',
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
		},
		blocks: {
			includeContent: false,
			sourceRoot: '../../styles/blocks/'
		},
	}
};

/**
 * Base Tasks
 */
function woo() {
	return gulp.src(sources.woo)
		.pipe(plumber(options.plumber))
		.pipe(sourcemaps.init())
		.pipe(sass(options.sass))
		.pipe(sourcemaps.write('./', options.sourcemaps.styles))
		.pipe(gulp.dest(folders.build));
}

function theme() {
	return gulp.src(sources.theme)
		.pipe(plumber(options.plumber))
		.pipe(sourcemaps.init())
		.pipe(sass(options.sass))
		.pipe(sourcemaps.write('./', options.sourcemaps.styles))
		.pipe(gulp.dest(folders.build));
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
		.pipe(sourcemaps.init())
		.pipe(sass(options.sass))
		.pipe(replace('../', '../../'))
		.pipe(sourcemaps.write('./', options.sourcemaps.blocks))
		.pipe(gulp.dest(folders.blocks));
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

function plugins() {
	return gulp.src(sources.plugins, {
			base: './',
			since: gulp.lastRun(plugins)
		})
		.pipe(plumber(options.plumber))
		.pipe(sourcemaps.init())
		.pipe(sass(options.sass))
		.pipe(sourcemaps.write('./', options.sourcemaps.styles))
		.pipe(gulp.dest('./'));
}

function scripts() {
	return gulp.src(sources.scripts)
		.pipe(plumber(options.plumber))
		.pipe(sourcemaps.init()).pipe(concat('scripts.js'))
		.pipe(uglify())
		.pipe(sourcemaps.write('./', options.sourcemaps.scripts))
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

/**
 * Default Exports
 */
exports.woo = woo;

exports.theme = theme;

exports.blocks = blocks;

exports.preview = preview;

exports.plugins = plugins;

exports.scripts = scripts;

exports.imagemin = images;

exports.build = gulp.parallel(woo, theme, blocks, preview, plugins, scripts);

exports.default = function() {

	gulp.watch(
		[
			'styles/theme.scss',
			'styles/base/*.scss',
			'styles/elements/*.scss',
			'styles/includes/*.scss',
		],
		gulp.parallel(theme)
	);

	gulp.watch(
		[
			'styles/woo.scss',
			'styles/woo/*.scss',
			'styles/elements/*.scss',
			'styles/includes/*.scss',
		],
		gulp.parallel(woo)
	);

	gulp.watch(
		[
			'styles/blocks/*.scss',
			'styles/elements/*.scss',
			'styles/includes/*.scss',
		],
		gulp.parallel(blocks)
	);

	gulp.watch(
		[
			'styles/**/*.scss',
		],
		gulp.parallel(preview)
	);

	gulp.watch(sources.plugins, gulp.parallel(plugins));

	gulp.watch(sources.scripts, gulp.parallel(scripts));

}