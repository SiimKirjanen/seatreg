var gulp  = require("gulp"),
	uglify = require("gulp-uglify"),
	browserSync = require('browser-sync'),
	concat = require('gulp-concat'),
	minifyCSS = require('gulp-minify-css'),
	reload = browserSync.reload;
	
gulp.task('registration-scripts', function() {
	  return gulp.src(['reg/js/date.format.js', 'reg/js/iscroll-zoom-5-1-3.js', 'reg/js/jquery.powertip.js', 'reg/js/registration.js'])
			.on('error', console.error.bind(console))
			.pipe(concat('registration.min.js'))
			.pipe(uglify())
			.pipe(gulp.dest('reg/js'));
}); 

gulp.task('registration-styles', function() {
	gulp.src(['reg/css/font-awesome.min.css', 'reg/css/registration.css', 'reg/css/jquery.powertip.css'])
		.on('error', console.error.bind(console))
		.pipe(concat('registration.min.css'))
		.pipe(minifyCSS())
		.pipe(gulp.dest('reg/css'))
		.pipe(reload({stream: true}));	
});