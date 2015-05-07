var gulp    = require( 'gulp' );
var phplint = require( 'phplint' ).lint;
var phpunit = require( 'gulp-phpunit' );

gulp.task( 'phplint', function( cb ) {
    phplint( [ './**/*.php', '!./node_modules/**/*' ], { limit: 10 }, function ( err, stdout, stderr ) {
        if ( err ) {
            cb( err );
        } else {
            cb();
        }
    } );
} );

gulp.task( 'phpunit', function() {
    return gulp.src( 'phpunit.xml' )
        .pipe(phpunit('phpunit'))
        .on('error', console.error('TESTS FAILED:\nYou killed someones baby!'))
        .pipe(function () { console.log('TESTS PASSED:\nAwesome you rock!'); });
});

gulp.task( 'default', [] );
