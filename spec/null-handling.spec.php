<?php
namespace phputil\sql;

describe( 'null handling function', function() {

    it( 'accepts two fields', function() {
        $r = ifNull( 'field1', 'field2' )->toString( DBType::MYSQL );
        expect( $r )->toBe( "COALESCE(`field1`, `field2`)" );
    } );

    it( 'accepts a field and a numeric value', function() {
        $r = ifNull( 'field1', val( 10 ) )->toString( DBType::MYSQL );
        expect( $r )->toBe( "COALESCE(`field1`, 10)" );
    } );

    it( 'accepts a field and a string value', function() {
        $r = ifNull( 'field1', val( 'hello' ) )->toString( DBType::MYSQL );
        expect( $r )->toBe( "COALESCE(`field1`, 'hello')" );
    } );

    it( 'accepts a field and a boolean value', function() {
        $r = ifNull( 'field1', val( false ) )->toString( DBType::MYSQL );
        expect( $r )->toBe( "COALESCE(`field1`, FALSE)" );
    } );

} );