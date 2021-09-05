var gulp  = require("gulp"),
	uglify = require("gulp-uglify"),
	browserSync = require('browser-sync'),
	concat = require('gulp-concat'),
	minifyCSS = require('gulp-minify-css'),
	gulpRev = require('gulp-rev'),
	del = require('del'),
	sass = require('gulp-sass')(require('sass')),
	reload = browserSync.reload;

function workingOnRegistrationJSFiles() {
	return gulp.src(['registration/js/date.format.js', 'registration/js/iscroll-zoom.js', 'js/jquery.powertip.js', 'registration/js/registration.js'])
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

function workingOnRegistrationCSSFiles() {
	return gulp.src(['registration/css/font-awesome.min.css',  'registration/css/index.scss', 'registration/css/jquery.powertip.css'])
		.on('error', console.error.bind(console))
		.pipe(sass())
		.pipe(concat('registration.min.css'))
		.pipe(minifyCSS())
		.pipe(gulpRev())
		.pipe(gulp.dest('registration/css'))
		.pipe(gulpRev.manifest({
			merge: true
		}))
		.pipe(gulp.dest('./'));
}

function workingOnAdminStyles() {
	return gulp.src(['css/scss/admin/seatreg_admin.scss'])
		.on('error', console.error.bind(console))
		.pipe(sass())
		.pipe(concat('seatreg_admin.min.css'))
		.pipe(minifyCSS())
		.pipe(gulp.dest('css'))
}

function workingOnBuilderStyles() {
	return gulp.src(['css/scss/builder/seatreg_builder.scss'])
		.on('error', console.error.bind(console))
		.pipe(sass())
		.pipe(concat('seatreg_builder.min.css'))
		.pipe(minifyCSS())
		.pipe(gulp.dest('css'))
}

gulp.task('clean:registration:css:cache', function () {
	return del([
		'registration/css/registration-*.min.css',
	]);
});

gulp.task('clean:registration:js:cache', function () {
	return del([
		'registration/js/registration-*.min.js',
	]);
});
	
gulp.task('registration-scripts', gulp.series('clean:registration:js:cache', workingOnRegistrationJSFiles)); 
gulp.task('registration-styles', gulp.series('clean:registration:css:cache', workingOnRegistrationCSSFiles));
gulp.task('builder-styles', gulp.series(workingOnBuilderStyles));
gulp.task('admin-styles', gulp.series(workingOnAdminStyles));


gulp.task('watch', function() {
	gulp.watch(['registration/js/*.js', '!registration/js/registration-*.min.js','registration/css/*.css', '!registration/css/registration-*.min.css'], 
	{ events: 'all' }, 
	gulp.series('registration-scripts', 'registration-styles'));
});