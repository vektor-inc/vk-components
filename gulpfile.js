const gulp = require('gulp');
const sass = require('gulp-sass')(require('sass'));
const plumber = require('gulp-plumber');
const postcss = require('gulp-postcss'); // PostCSS をインポート
const autoprefixer = require('autoprefixer'); // Autoprefixer をインポート
const cleanCss = require('gulp-clean-css'); // CSS 圧縮
const combineMediaQuery = require('postcss-combine-media-query'); // メディアクエリ統合

gulp.task('build', function () {
    return gulp.src('src/assets/scss/**/*.scss') // .scss ファイルのみ対象
        .pipe(plumber()) // エラーを防ぐ
        .pipe(sass()) // Sass をコンパイル
        .pipe(postcss([autoprefixer(), combineMediaQuery()])) // Autoprefixer とメディアクエリ統合を適用
        .pipe(cleanCss()) // CSS を圧縮
        .pipe(gulp.dest('./src/assets/css/')); // 出力先ディレクトリ
});



