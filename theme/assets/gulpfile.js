let fs = require('fs'),
	gulp = require('gulp'),
	sass = require('gulp-sass'),
	notify = require('gulp-notify'),
	concat = require('gulp-concat'),
	plumber = require('gulp-plumber'),
	sourcemaps = require('gulp-sourcemaps'),
	insert = require('gulp-insert'),
	uglify = require('gulp-uglify-es').default;

if (typeof sass.compiler === 'undefined') {
	sass = sass(require('node-sass'));
}

let folders = {
	build: './build',
	styles: './styles',
	scripts: './scripts',
	blocks: './build/blocks'
};

let sources = {
	woo: 'styles/woo.scss',
	theme: 'styles/theme.scss',
	blocks: 'styles/blocks/*.scss',
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

	const elements = searchElements();

	return gulp.src(sources.blocks, {
			base: './styles/blocks',
			since: gulp.lastRun(blocks)
		})
		.pipe(plumber(options.plumber))
		.pipe(insert.transform(function(contents, file) {
			return injectImports(contents, elements);
		}))
		.pipe(sourcemaps.init())
		.pipe(sass(options.sass))
		.pipe(insert.transform(function(contents, file) {
			return contents.replaceAll('url(../images/', 'url(../../images/');
		}))
		.pipe(sourcemaps.write('./', options.sourcemaps.blocks))
		.pipe(gulp.dest(folders.blocks));
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

/**
 * Additional functions
 *
 * @param {string} contents
 * @param {object} elements
 *
 * @returns {string}
 */
function injectImports(contents, elements) {

	let requiredImports = [],
		importRegex = /@import '?(\.\.\/[\w\-]+\/[\w\-]+)(.scss)?'?;/g,
		elementRegex = /@extend\s+(%[\w\-]+);/g;

	let existingImports = Array.from(contents.matchAll(importRegex), match => match[1]),
		existingElements = Array.from(contents.matchAll(elementRegex), match => match[1]);

	if (contents.indexOf('@include') !== -1) {
		requiredImports.push('../includes/variables');
		requiredImports.push('../includes/mixins');
	} else if (contents.indexOf('$') !== -1) {
		requiredImports.push('../includes/variables');
	}

	existingElements.forEach(element => {
		if (elements[element] && requiredImports.indexOf(elements[element]) === -1) {
			requiredImports.push(elements[element]);
		}
	});

	requiredImports = requiredImports.filter(function(value, index, array) {
		return array.indexOf(value) === index;
	});

	let addImports = requiredImports.filter(link => existingImports.indexOf(link) === -1),
		removeImports = existingImports.filter(link => requiredImports.indexOf(link) === -1);

	if (addImports.length > 0 || removeImports.length > 0 || requiredImports.length !== existingImports.length) {

		existingImports.forEach(link => {
			contents = contents.replaceAll('@import \'' + link + '\';', '');
		});

		contents = contents.trim();

		contents = requiredImports.map(link => {
			return '@import \'' + link + '\';';
		}).join('\n') + '\n' + contents;

	}

	return contents;

}

/**
 * Get a list of elements with paths
 *
 * @returns {object}
 */
function searchElements() {

	let elementsFolder = './styles/elements/',
		elementMap = {};

	fs.readdirSync(elementsFolder).forEach(function(file) {

		let data = fs.readFileSync(elementsFolder + file, 'utf8'),
			regex = /(%[\w\-]+)\s*{/g;

		let results = Array.from(data.matchAll(regex), match => match[1]);

		results.forEach(match => {
			elementMap[match] = '../elements/' + file.replace('.scss', '');
		});

	});

	return elementMap;

}

/**
 * Default Exports
 */
exports.woo = woo;

exports.theme = theme;

exports.blocks = blocks;

exports.plugins = plugins;

exports.scripts = scripts;

exports.build = gulp.parallel(woo, theme, blocks, plugins, scripts);

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

	gulp.watch(sources.plugins, gulp.parallel(plugins));

	gulp.watch(sources.scripts, gulp.parallel(scripts));

}