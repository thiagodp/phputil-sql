<?php
namespace phputil\sql;

describe( 'having', function() {

    it( 'accepts a condition', function() {
        $r = select( 1 )->from( 'foo' )->groupBy( 1 )->having(
            val( 1 )->greaterThan( 0 )
        )->endAsString();
        expect( $r )->toBe( 'SELECT 1 FROM foo GROUP BY 1 HAVING 1 > 0' );
    } );

    it( 'accepts an aggregate function', function() {
        $r = select( 1 )->from( 'foo' )->groupBy( 1 )->having(
            val( count( 'foo' ) )->greaterThan( val( count( 1 ) ) )
        )->endAsString( SQLType::MYSQL );
        expect( $r )->toBe( 'SELECT 1 FROM `foo` GROUP BY 1 HAVING COUNT(`foo`) > COUNT(1)' );
    } );

} );