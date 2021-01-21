var gulp = require('gulp'),
	sass = require('gulp-sass'),
	babel = require('gulp-babel'),
	csso = require('gulp-csso'),
	notify = require('gulp-notify'),
	concat = require('gulp-concat'),
	csssvg = require('gulp-css-svg'),
	plumber = require('gulp-plumber'),
	imagemin = require('gulp-imagemin'),
	gloablize = require('gulp-sass-glob'),
	sourcemaps = require('gulp-sourcemaps');

var folders = {
	build: './build',
	styles: './styles',
	scripts: './scripts',
	images: './images',
};

var sources = {
	style: 'styles/style.scss',
	styles: [
		'styles/*.scss',
		'styles/base/*.scss',
		'styles/blocks/*.scss',
		'styles/elements/*.scss',
		'styles/includes/*.scss'
	],
	scripts: 'scripts/*.js'
};

var options = {
	plumber: {
		errorHandler: notify.onError({
			message: "<%= error.message %>",
			sound: true
		})
	},
	csso: {
		cascade: false,
	},
	csssvg: {
		baseDir: '../images',
		maxWeightResource: 4096
	},
	sass: {
		outputStyle: 'expanded',
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
	},
	browsersync: {
		server: {
			baseDir: './'
		},
		notify: false
	}
};


gulp.task('imagemin', function() {

	return gulp.src(folders.images + '/**/*')
		.pipe(plumber(options.plumber))
		.pipe(imagemin())
		.pipe(gulp.dest(folders.images));

});


gulp.task('scripts', function() {

	return gulp.src(sources.scripts)
		.pipe(plumber(options.plumber))
		.pipe(sourcemaps.init())
		.pipe(concat('scripts.js'))
		.pipe(babel({
			'presets': [
				[require("@babel/preset-env"), {
					debug: false,
					useBuiltIns: false,
				}]
			],
			'compact': true
		}))
		.pipe(sourcemaps.write('./', options.sourcemaps.scripts))
		.pipe(gulp.dest(folders.build));

});


gulp.task('styles', function() {

	return gulp.src(sources.style)
		.pipe(plumber(options.plumber))
		.pipe(sourcemaps.init())
		.pipe(gloablize())
		.pipe(sass(options.sass))
		.pipe(sourcemaps.write('./', options.sourcemaps.styles))
		.pipe(csssvg(options.csssvg))
		//.pipe(csso(options.csso))
		.pipe(gulp.dest(folders.build));

});


gulp.task('default', function() {
	gulp.watch(sources.styles, gulp.parallel('styles'));
	gulp.watch(sources.scripts, gulp.parallel('scripts'));
});