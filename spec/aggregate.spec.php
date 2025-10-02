<?php
namespace phputil\sql;

describe( 'aggregate', function() {

    it( 'accepts a value', function() {
        $r = count( '*' )->toString();
        expect( $r )->toBe( "COUNT(*)" );
    } );

    it( 'accepts a column name', function() {
        $r = count( col('a') )->toString( DBType::MYSQL );
        expect( $r )->toBe( "COUNT(`a`)" );
    } );

    it( 'accepts a column name by default', function() {
        $r = count( 'a' )->toString( DBType::MYSQL );
        expect( $r )->toBe( "COUNT(`a`)" );
    } );

} );