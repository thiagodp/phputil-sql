<?php
namespace phputil\sql;

describe( 'null handling function', function() {

    it( 'accepts two fields', function() {
        $r = ifNull( 'field1', 'field2' )->toString( SQLType::MYSQL );
        expect( $r )->toBe( "COALESCE(`field1`, `field2`)" );
    } );

    it( 'accepts a field and a numeric value', function() {
        $r = ifNull( 'field1', val( 10 ) )->toString( SQLType::MYSQL );
        expect( $r )->toBe( "COALESCE(`field1`, 10)" );
    } );

    it( 'accepts a field and a string value', function() {
        $r = ifNull( 'field1', val( 'hello' ) )->toString( SQLType::MYSQL );
        expect( $r )->toBe( "COALESCE(`field1`, 'hello')" );
    } );

    it( 'accepts a field and a boolean value', function() {
        $r = ifNull( 'field1', val( false ) )->toString( SQLType::MYSQL );
        expect( $r )->toBe( "COALESCE(`field1`, FALSE)" );
    } );

    it( 'accepts a function as field', function() {
        $r = ifNull( ifNull('a', 'b'), val( false ) )->toString( SQLType::MYSQL );
        expect( $r )->toBe( "COALESCE(COALESCE(`a`, `b`), FALSE)" );
    } );

    it( 'accepts a function as value', function() {
        $r = ifNull( 'a', concat( 'b', 'c' ) )->toString( SQLType::MYSQL );
        expect( $r )->toBe( "COALESCE(`a`, CONCAT(`b`, `c`))" );
    } );
} );