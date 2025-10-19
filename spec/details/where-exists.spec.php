<?php
namespace phputil\sql;

describe( 'whereExists', function() {

    it( 'accepts a selection', function() {
        $r = select()->from( 'example' )->whereExists(
            select( 1 )
        )->endAsString();
        expect( $r )->toBe( 'SELECT * FROM example WHERE EXISTS (SELECT 1)' );
    } );

} );