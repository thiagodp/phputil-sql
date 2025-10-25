<?php
namespace phputil\sql;

describe( 'limit and offset', function() {

    it( 'can make a limit', function() {
        $r = select()->from( 'a' )
            ->limit( 10 )
            ->endAsString( SQLType::MYSQL );
        expect( $r )->toBe( 'SELECT * FROM `a` LIMIT 10' );
    } );

    it( 'can make an offset', function() {
        $r = select()->from( 'a' )
            ->offset( 2 )
            ->endAsString( SQLType::MYSQL );
        expect( $r )->toBe( 'SELECT * FROM `a` OFFSET 2' );
    } );

    it( 'makes a limit then an offset', function() {
        $r = select()->from( 'a' )
            ->limit( 10 )
            ->offset( 2 )
            ->endAsString( SQLType::MYSQL );
        expect( $r )->toBe( 'SELECT * FROM `a` LIMIT 10 OFFSET 2' );
    } );

} );