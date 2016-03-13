gulp    = require 'gulp'
sass    = require 'gulp-sass'
coffee  = require 'gulp-coffee'
sort    = require 'gulp-sort'
wpPot   = require 'gulp-wp-pot'
gettext = require 'gulp-gettext'
plumber = require 'gulp-plumber'

meta = require './package.json'

gulp.task 'sass', ->
    gulp.src './assets/*.scss'
        .pipe plumber()
        .pipe sass()
        .pipe gulp.dest 'assets/'

gulp.task 'coffee', ->
    gulp.src './assets/*.coffee'
        .pipe plumber()
        .pipe coffee bare:false
        .pipe gulp.dest 'assets/'


gulp.task 'watch',['build'], ->
    gulp.watch ['assets/*','./*.php'],['build']

gulp.task 'build', ['sass', 'coffee']
