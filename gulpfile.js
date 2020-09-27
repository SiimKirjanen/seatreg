var gulp  = require("gulp"),
	uglify = require("gulp-uglify"),
	sass = require('gulp-ruby-sass'),
	browserSync = require('browser-sync'),
	imagemin = require('gulp-imagemin'),
	pngquant = require('imagemin-pngquant'),
	autoprefixer = require('gulp-autoprefixer'),
	concat = require('gulp-concat'),
	minifyCSS = require('gulp-minify-css'),
	rename = require("gulp-rename"),
	reload = browserSync.reload;
	
//Scripts Task
gulp.task('registration-scripts', function() {
	  return gulp.src(['reg/js/date.format.js', 'reg/js/iscroll-zoom-5-1-3.js', 'reg/js/jquery.powertip.js', 'reg/js/view.js'])
			.on('error', console.error.bind(console))
			.pipe(concat('view.all.min.js'))
			.pipe(uglify())
			.pipe(gulp.dest('reg/js/'));
}); 

//don't remember why build-scripts is not used
gulp.task('build-scripts', function() {
	  return gulp.src(['js/build.js'])
			.on('error', console.error.bind(console))
			.pipe(concat('build.min.js'))
			.pipe(uglify())
			.pipe(gulp.dest('js/'));
}); 

gulp.task('registration-styles', function() {
	gulp.src(['reg/css/font-awesome.min.css', 'reg/css/view3.css', 'reg/css/jquery.powertip.css'])
		.on('error', console.error.bind(console))
		.pipe(concat('view.all.min.css'))
		.pipe(minifyCSS())
		.pipe(gulp.dest('reg/css/'))
		.pipe(reload({stream: true}));	
});

//Watches JS
gulp.task('watch', function() {

	browserSync({
        server: "./"
    });
    
	gulp.watch('js/*.js', ['scripts', reload]);
	gulp.watch('scss/**/*.scss', ['styles']);
	gulp.watch("./*.html").on('change', reload);
});

gulp.task('bs-reload', function () {
    browserSync.reload();
});