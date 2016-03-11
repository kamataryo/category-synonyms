gulp    = require 'gulp'
sass    = require 'gulp-sass'
coffee  = require 'gulp-coffee'
plumber = require 'gulp-plumber'

gulp.task 'sass', ->
    gulp.src 'assets/*.scss'
        .pipe plumber()
        .pipe sass()
        .pipe gulp.dest 'assets/'

gulp.task 'coffee', ->
    gulp.src 'assets/*.coffee'
        .pipe plumber()
        .pipe coffee bare:false
        .pipe gulp.dest 'assets/'

gulp.task 'watch',['build'], ->
    gulp.watch ['assets/*'],['build']

gulp.task 'build', ['sass', 'coffee']
