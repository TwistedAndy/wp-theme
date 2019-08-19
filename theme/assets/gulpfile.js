var gulp = require('gulp'),
	sass = require('gulp-sass'),
	csso = require('postcss-csso'),
	notify = require('gulp-notify'),
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
	autoprefixer = require('gulp-autoprefixer');

var folder = {
	css: './css',
	scss: './scss',
	icons: './images',
	images: './images',
};

var options = {
	autoprefixer: {
		browsers: ['last 2 versions', 'ie 10'],
		cascade: false,
		grid: 'autoplace'
	},
	plumber: {
		errorHandler: notify.onError({
			message: "<%= error.message %>",
			sound: true
		})
	},
	postcss: [
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
			comments: false
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
				sprite: folder.images + '/sprite.svg',
				bust: false,
				render: {
					scss: {
						dest: folder.scss + '/includes/sprite.svg.scss',
						template: folder.scss + '/includes/sprite.svg.template'
					}
				}
			},
		},
	},
	sprite_png: {
		imgName: folder.images + '/sprite.png',
		cssTemplate: folder.scss + '/includes/sprite.png.template',
		cssName: folder.scss + '/includes/sprite.png.scss',
		algorithm: 'top-down'
	},
	sass: {
		outputStyle: 'expanded',
		indentType: 'tab',
		indentWidth: 1
	},
	styles: [
		'scss/*.scss',
		'scss/base/*.scss',
		'scss/blocks/*.scss',
		'scss/includes/*.scss'
	],
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


gulp.task('imagemin', function() {

	return gulp.src(folder.images + '/**/*')
	.pipe(plumber(options.plumber))
	.pipe(imagemin())
    .pipe(gulp.dest(folder.images));

});


gulp.task('sprite_svg', function() {

	return gulp.src(folder.icons + '/ico_*.svg')
	.pipe(plumber(options.plumber))
	.pipe(sprite_svg(options.sprite_svg))
	.pipe(gulp.dest('./'));

});


gulp.task('sprite_png', function() {

	return gulp.src(folder.icons + '/ico_*.png')
	.pipe(plumber(options.plumber))
	.pipe(sprite_png(options.sprite_png))
	.pipe(gulp.dest('./'));

});


gulp.task('scss_fast', function() {

	return gulp.src(folder.scss + '/*.scss')
	.pipe(plumber(options.plumber))
	.pipe(sourcemaps.init())
	.pipe(gloablize())
	.pipe(sass(options.sass))
	.pipe(sourcemaps.write('./'))
	.pipe(gulp.dest(folder.css));

});


gulp.task('scss_full', function() {

	return gulp.src(folder.scss + '/*.scss')
	.pipe(plumber(options.plumber))
	.pipe(gloablize())
	.pipe(sass(options.sass))
	.pipe(csssvg(options.csssvg))
	.pipe(autoprefixer(options.autoprefixer))
	.pipe(postcss(options.postcss))
	.pipe(beautify(options.beautify))
	.pipe(gulp.dest(folder.css));

});


gulp.task('watch', function() {

	gulp.watch(options.styles, gulp.parallel('scss_fast'));

});


gulp.task('default', gulp.parallel('watch'));