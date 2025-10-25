<?php
namespace phputil\sql;

describe( 'union', function() {

    it( 'accepts a selection', function() {
        $r = select()->from( 'a' )
            ->union( select()->from( 'b' )->end() )
            ->endAsString();
        expect( $r )->toBe( 'SELECT * FROM a UNION SELECT * FROM b' );
    } );

    it( 'supports multiple occurrences', function() {
        $r = select()->from( 'a' )
            ->union( select()->from( 'b' )->end() )
            ->union( select()->from( 'c' )->end() )
            ->endAsString();
        expect( $r )->toBe( 'SELECT * FROM a UNION SELECT * FROM b UNION SELECT * FROM c' );
    } );

    it( 'accepts using distinct', function() {
        $r = select()->from( 'a' )
            ->unionDistinct( select()->from( 'b' )->end() )
            ->endAsString();
        expect( $r )->toBe( 'SELECT * FROM a UNION DISTINCT SELECT * FROM b' );
    } );

    it( 'supports multiple occurrences', function() {
        $r = select()->from( 'a' )
            ->unionDistinct( select()->from( 'b' )->end() )
            ->unionDistinct( select()->from( 'c' )->end() )
            ->endAsString();
        expect( $r )->toBe( 'SELECT * FROM a UNION DISTINCT SELECT * FROM b UNION DISTINCT SELECT * FROM c' );
    } );

} );