/**
 * Gulp configuration file for eightyfourem theme
 *
 * This file defines tasks for optimizing CSS and JS files in the theme.
 */

const gulp = require('gulp');
const cleanCSS = require('gulp-clean-css');
const terser = require('gulp-terser');
const rename = require('gulp-rename');
const sourcemaps = require('gulp-sourcemaps');
const autoprefixer = require('gulp-autoprefixer').default;
const { series, parallel, watch } = require('gulp');
const { deleteAsync } = require('del');

// File paths
const paths = {
  styles: {
    theme: [
      './assets/css/navigation.css',
      './assets/css/page-specific.css',
      './assets/css/utilities.css',
      './assets/css/sticky-header.css',
      './assets/css/case-study-filter.css',
      './assets/css/related-case-studies.css',
      './assets/css/search.css',
      './assets/css/modal-search.css',
      './assets/css/faq-search.css',
      './assets/css/sitemap.css',
      './assets/css/animations.css'
    ],
    googleReviews: [
      './blocks/google-reviews/style.css',
      './blocks/google-reviews/editor.css'
    ],
    calendlyBooking: [
      './blocks/calendly-booking-details/style.css',
      './blocks/calendly-booking-details/editor.css'
    ],
    highlight: './assets/css/highlight.css',
    dest: './assets/css/',
  },
  scripts: {
    theme: [
      './assets/js/sticky-header.js',
      './assets/js/case-study-filter.js',
      './assets/js/modal-search.js',
      './assets/js/faq-search.js',
      './assets/js/animations.js'
    ],
    googleReviews: './blocks/google-reviews/index.js',
    calendlyBooking: './blocks/calendly-booking-details/index.js',
    highlight: './assets/js/highlight.js',
    dest: './assets/js/'
  }
};

// Clean task - removes previously generated .min files
function clean() {
  return deleteAsync([
    './assets/css/*.min.css',
    './assets/css/*.min.css.map',
    './assets/js/*.min.js',
    './assets/js/*.min.js.map',
    './blocks/google-reviews/*.min.css',
    './blocks/google-reviews/*.min.css.map',
    './blocks/google-reviews/*.min.js',
    './blocks/google-reviews/*.min.js.map',
    './blocks/calendly-booking-details/*.min.css',
    './blocks/calendly-booking-details/*.min.css.map',
    './blocks/calendly-booking-details/*.min.js',
    './blocks/calendly-booking-details/*.min.js.map'
  ]);
}

// CSS optimization task - Theme files
function stylesTheme() {
  return gulp.src(paths.styles.theme)
    .pipe(sourcemaps.init())
    .pipe(autoprefixer({
      overrideBrowserslist: ['last 2 versions'],
      cascade: true
    }))
    .pipe(cleanCSS({
      compatibility: 'ie8',
      level: {
        1: {
          specialComments: 0
        }
      }
    }))
    .pipe(rename({ suffix: '.min' }))
    .pipe(sourcemaps.write('./'))
    .pipe(gulp.dest(paths.styles.dest));
}

// CSS optimization task - Google Reviews
function stylesGoogleReviews() {
  return gulp.src(paths.styles.googleReviews)
    .pipe(sourcemaps.init())
    .pipe(cleanCSS({
      compatibility: 'ie8',
      level: {
        1: {
          specialComments: 0
        }
      }
    }))
    .pipe(rename({ suffix: '.min' }))
    .pipe(sourcemaps.write('./'))
    .pipe(gulp.dest('./blocks/google-reviews/'));
}

// CSS optimization task - Highlight
function stylesHighlight() {
  return gulp.src(paths.styles.highlight)
    .pipe(sourcemaps.init())
    .pipe(cleanCSS({
      compatibility: 'ie8',
      level: {
        1: {
          specialComments: 0
        }
      }
    }))
    .pipe(rename({ suffix: '.min' }))
    .pipe(sourcemaps.write('./'))
    .pipe(gulp.dest(paths.styles.dest));
}

// CSS optimization task - Calendly Booking
function stylesCalendlyBooking() {
  return gulp.src(paths.styles.calendlyBooking)
    .pipe(sourcemaps.init())
    .pipe(cleanCSS({
      compatibility: 'ie8',
      level: {
        1: {
          specialComments: 0
        }
      }
    }))
    .pipe(rename({ suffix: '.min' }))
    .pipe(sourcemaps.write('./'))
    .pipe(gulp.dest('./blocks/calendly-booking-details/'));
}

// Combined styles task
const styles = parallel(stylesTheme, stylesGoogleReviews, stylesHighlight, stylesCalendlyBooking);

// JavaScript optimization task - Theme files
function scriptsTheme() {
  return gulp.src(paths.scripts.theme)
    .pipe(sourcemaps.init())
    .pipe(terser({
      compress: {
        drop_console: false
      }
    }))
    .pipe(rename({ suffix: '.min' }))
    .pipe(sourcemaps.write('./'))
    .pipe(gulp.dest(paths.scripts.dest));
}

// JavaScript optimization task - Google Reviews
function scriptsGoogleReviews() {
  return gulp.src(paths.scripts.googleReviews)
    .pipe(sourcemaps.init())
    .pipe(terser({
      compress: {
        drop_console: true
      }
    }))
    .pipe(rename({ suffix: '.min' }))
    .pipe(sourcemaps.write('./'))
    .pipe(gulp.dest('./blocks/google-reviews/'));
}

// JavaScript optimization task - Highlight
function scriptsHighlight() {
  return gulp.src(paths.scripts.highlight)
    .pipe(sourcemaps.init())
    .pipe(terser({
      compress: {
        drop_console: true
      }
    }))
    .pipe(rename({ suffix: '.min' }))
    .pipe(sourcemaps.write('./'))
    .pipe(gulp.dest(paths.scripts.dest));
}

// JavaScript optimization task - Calendly Booking
function scriptsCalendlyBooking() {
  return gulp.src(paths.scripts.calendlyBooking)
    .pipe(sourcemaps.init())
    .pipe(terser({
      compress: {
        drop_console: true
      }
    }))
    .pipe(rename({ suffix: '.min' }))
    .pipe(sourcemaps.write('./'))
    .pipe(gulp.dest('./blocks/calendly-booking-details/'));
}

// Combined scripts task
const scripts = parallel(scriptsTheme, scriptsGoogleReviews, scriptsHighlight, scriptsCalendlyBooking);

// Watch task for development
function watchFiles() {
  watch(paths.styles.theme, stylesTheme);
  watch(paths.styles.googleReviews, stylesGoogleReviews);
  watch(paths.styles.highlight, stylesHighlight);
  watch(paths.styles.calendlyBooking, stylesCalendlyBooking);
  watch(paths.scripts.theme, scriptsTheme);
  watch(paths.scripts.googleReviews, scriptsGoogleReviews);
  watch(paths.scripts.highlight, scriptsHighlight);
  watch(paths.scripts.calendlyBooking, scriptsCalendlyBooking);
}

// Define complex tasks
const build = series(clean, parallel(styles, scripts));
const dev = series(build, watchFiles);

// Export tasks
exports.clean = clean;
exports.styles = styles;
exports.scripts = scripts;
exports.watch = watchFiles;
exports.build = build;
exports.default = dev;
