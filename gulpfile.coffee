gulp    = require 'gulp'
sass    = require 'gulp-sass'
plumber = require 'plumber'

gulp.task 'sass', ->
    gulp.src 'assets/*.scss'
        .pipe plumber()
        .pipe sass()
        .pipe gulp.dest 'assets/'

gulp.task 'build', ['sass']
