/**
 * @file plugins/generic/jatsParser/gulpfile.js
 *
 * Copyright (c) 2017-2018 Vitalii Bezsheiko
 * Distributed under the GNU GPL v3.
 *
 */

var gulp = require('gulp');
var sass = require('gulp-sass');
var concat = require('gulp-concat');
var concatCss = require('gulp-concat-css');
var minifyCSS = require('gulp-csso');
var sourcemaps = require('gulp-sourcemaps');
var minify = require('gulp-minify');

gulp.task('sass', function () {
	//gulp.src(['node_modules/bootstrap/scss/bootstrap.scss', 'node_modules/@fortawesome/fontawesome-free-webfonts/scss/fontawesome.scss', 'resources/scss/**/*.scss'])
	gulp.src('resources/scss/**/*.scss')
		.pipe(sass())
		.pipe(concat('app.min.css'))
		.pipe(minifyCSS())
		.pipe(gulp.dest('app'))
});

gulp.task('scripts', function () {
	//gulp.src(['node_modules/jquery/dist/jquery.js', 'node_modules/popper.js/dist/umd/popper.js', 'node_modules/bootstrap/dist/js/bootstrap.js', 'resources/javascript/**/*.js'])
	gulp.src(['resources/javascript/**/*.js'])
		.pipe(sourcemaps.init())
		.pipe(concat('app.js'))
		.pipe(sourcemaps.write())
		.pipe(gulp.dest('app'));
});

gulp.task('compress', function () {
	gulp.src('app/app.js')
		.pipe(minify({
			ext: {
				src: '-debug.js',
				min: '.min.js'
			},
			exclude: ['tasks'],
			ignoreFiles: ['.combo.js', '-min.js']
		}))
		.pipe(gulp.dest('app'))
});

gulp.task('watch', function () {
	gulp.watch('resources/scss/**/*.scss', ['sass']);
	gulp.watch('resources/javascript/**/*.js', ['scripts']);
});