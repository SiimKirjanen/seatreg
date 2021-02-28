var gulp  = require("gulp"),
	uglify = require("gulp-uglify"),
	browserSync = require('browser-sync'),
	concat = require('gulp-concat'),
	minifyCSS = require('gulp-minify-css'),
	gulpRev = require('gulp-rev'),
	del = require('del'),
	reload = browserSync.reload;

function workingOnJSFiles() {
	return gulp.src(['registration/js/date.format.js', 'registration/js/iscroll-zoom-5-1-3.js', 'registration/js/jquery.powertip.js', 'registration/js/registration.js'])
			.on('error', console.error.bind(console))
			.pipe(concat('registration.min.js'))
			.pipe(uglify())
			.pipe(gulpRev())
			.pipe(gulp.dest('registration/js'))
			.pipe(gulpRev.manifest({
				merge: true
			}))
			.pipe(gulp.dest('./'));
}

function workingOnCSSFiles() {
	return gulp.src(['registration/css/font-awesome.min.css', 'registration/css/registration.css', 'registration/css/jquery.powertip.css'])
		.on('error', console.error.bind(console))
		.pipe(concat('registration.min.css'))
		.pipe(minifyCSS())
		.pipe(gulpRev())
		.pipe(gulp.dest('registration/css'))
		.pipe(gulpRev.manifest({
			merge: true
		}))
		.pipe(gulp.dest('./'));
}

gulp.task('clean:css:cache', function () {
	return del([
		'registration/css/registration-*.min.css',
	]);
});

gulp.task('clean:js:cache', function () {
	return del([
		'registration/js/registration-*.min.js',
	]);
});
	
gulp.task('registration-scripts', gulp.series('clean:js:cache', workingOnJSFiles)); 

gulp.task('registration-styles', gulp.series('clean:css:cache', workingOnCSSFiles));

gulp.task('watch', function() {
	gulp.watch(['registration/js/*.js', '!registration/js/registration-*.min.js','registration/css/*.css', '!registration/css/registration-*.min.css'], 
	{ events: 'all' }, 
	gulp.series('registration-scripts', 'registration-styles'));
});