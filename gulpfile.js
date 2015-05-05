var gulp = require( 'gulp' );
var phplint = require('phplint').lint;

gulp.task( 'phplint', function( cb ) {
    phplint( [ './**/*.php', '!./node_modules/**/*' ], { limit: 10 }, function ( err, stdout, stderr ) {
        if ( err ) {
            cb( err );
        } else {
            cb();
        }
    } );
} );

gulp.task( 'default', [] );
