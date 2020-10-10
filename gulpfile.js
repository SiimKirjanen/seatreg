var gulp  = require("gulp"),
	uglify = require("gulp-uglify"),
	browserSync = require('browser-sync'),
	concat = require('gulp-concat'),
	minifyCSS = require('gulp-minify-css'),
	reload = browserSync.reload;
	
gulp.task('registration-scripts', function() {
	  return gulp.src(['registration/js/date.format.js', 'registration/js/iscroll-zoom-5-1-3.js', 'registration/js/jquery.powertip.js', 'registration/js/registration.js'])
			.on('error', console.error.bind(console))
			.pipe(concat('registration.min.js'))
			.pipe(uglify())
			.pipe(gulp.dest('registration/js'));
}); 

gulp.task('registration-styles', function() {
	gulp.src(['registration/css/font-awesome.min.css', 'registration/css/registration.css', 'registration/css/jquery.powertip.css'])
		.on('error', console.error.bind(console))
		.pipe(concat('registration.min.css'))
		.pipe(minifyCSS())
		.pipe(gulp.dest('registration/css'))
		.pipe(reload({stream: true}));	
});