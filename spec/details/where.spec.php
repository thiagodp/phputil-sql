<?php
namespace phputil\sql;

describe( 'where', function() {

    it( 'accepts a condition', function() {
        $r = select()->from( 'example' )
            ->where( val( 1 )->equalTo( 1 ) )
            ->endAsString();
        expect( $r )->toBe( 'SELECT * FROM example WHERE 1 = 1' );
    } );

    describe( 'whereExists', function() {

        it( 'can have a selection', function() {
            $r = select()->from( 'example' )
                ->whereExists( select( 1 ) )
                ->endAsString();
            expect( $r )->toBe( 'SELECT * FROM example WHERE EXISTS (SELECT 1)' );
        } );

    } );

} );