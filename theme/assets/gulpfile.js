var gulp = require('gulp'),
	sass = require('gulp-sass'),
	babel = require('gulp-babel'),
	csso = require('postcss-csso'),
	notify = require('gulp-notify'),
	concat = require('gulp-concat'),
	csssvg = require('gulp-css-svg'),
	plumber = require('gulp-plumber'),
	postcss = require('gulp-postcss'),
	mqpacker = require('css-mqpacker'),
	imagemin = require('gulp-imagemin'),
	browsersync = require('browser-sync'),
	gloablize = require('gulp-sass-glob'),
	beautify = require('gulp-cssbeautify'),
	sourcemaps = require('gulp-sourcemaps'),
	sprite_svg = require('gulp-svg-sprite'),
	sprite_png = require('gulp.spritesmith'),
	autoprefixer = require('autoprefixer');

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
	postcss: [
		autoprefixer({
			cascade: false,
			grid: 'autoplace',
		}),
		mqpacker({
			sort: function(a, b) {
				var A = a.replace(/\D/g, ''),
					B = b.replace(/\D/g, ''),
					isMinA = /min-width/.test(a),
					isMaxA = /max-width/.test(a),
					isMinB = /min-width/.test(b),
					isMaxB = /max-width/.test(b);

				if (isMaxA && isMaxB) {
					return B - A;
				} else if (isMinA && isMinB) {
					return A - B;
				} else if (isMaxA && isMinB) {
					return 1;
				} else if (isMinA && isMaxB) {
					return -1;
				}

				return 1;
			}
		}),
		csso({
			comments: false,
		}),
	],
	csssvg: {
		baseDir: '../images',
		maxWeightResource: 4096
	},
	beautify: {
		indent: "\t",
		openbrace: 'end-of-line',
		autosemicolon: true
	},
	sprite_svg: {
		mode: {
			css: {
				dest: './',
				layout: 'packed',
				sprite: folders.images + '/sprite.svg',
				bust: false,
				render: {
					scss: {
						dest: folders.styles + '/includes/sprite.svg.scss',
						template: folders.styles + '/includes/sprite.svg.template'
					}
				}
			},
		},
	},
	sprite_png: {
		imgName: folders.images + '/sprite.png',
		cssTemplate: folders.styles + '/includes/sprite.png.template',
		cssName: folders.styles + '/includes/sprite.png.scss',
		algorithm: 'top-down'
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


gulp.task('browsersync', function() {
	browsersync.init(options.browsersync);
});


gulp.task('sprite_svg', function() {

	return gulp.src(folders.images + '/ico_*.svg')
		.pipe(plumber(options.plumber))
		.pipe(sprite_svg(options.sprite_svg))
		.pipe(gulp.dest('./'));

});


gulp.task('sprite_png', function() {

	return gulp.src(folders.images + '/ico_*.png')
		.pipe(plumber(options.plumber))
		.pipe(sprite_png(options.sprite_png))
		.pipe(gulp.dest('./'));

});


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


gulp.task('styles_fast', function() {

	return gulp.src(sources.style)
		.pipe(plumber(options.plumber))
		.pipe(sourcemaps.init())
		.pipe(gloablize())
		.pipe(sass(options.sass))
		.pipe(sourcemaps.write('./', options.sourcemaps.styles))
		.pipe(gulp.dest(folders.build));

});


gulp.task('styles_full', function() {

	return gulp.src(sources.style)
		.pipe(plumber(options.plumber))
		.pipe(sourcemaps.init())
		.pipe(gloablize())
		.pipe(sass(options.sass))
		.pipe(postcss(options.postcss))
		.pipe(sourcemaps.write('./', options.sourcemaps.styles))
		//.pipe(csssvg(options.csssvg))
		//.pipe(beautify(options.beautify))
		.pipe(gulp.dest(folders.build));

});


gulp.task('watch', function() {

	gulp.watch(sources.styles, gulp.parallel('styles_fast'));

	gulp.watch(sources.scripts, gulp.parallel('scripts'));

});


gulp.task('default', gulp.parallel('watch'));