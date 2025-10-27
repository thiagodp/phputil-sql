<?php
namespace phputil\sql;

describe( 'string functions', function() {

    describe( 'upper', function() {

        it( 'accepts a field name', function() {
            $r = upper( 'a' )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'UPPER(`a`)' );
        } );

        it( 'accepts a value', function() {
            $r = upper( val( 'Hello' ) )->toString( SQLType::MYSQL );
            expect( $r )->toBe( "UPPER('Hello')" );
        } );

        it( 'can have an alias', function() {
            $r = upper( 'a' )->as( 'foo' )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'UPPER(`a`) AS `foo`' );
        } );

        it( 'accepts another function as value', function() {
            $r = upper( concat( 'a', 'b' ) )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'UPPER(CONCAT(`a`, `b`))' );
        } );
    } );


    describe( 'lower', function() {

        it( 'accepts a field name', function() {
            $r = lower( 'a' )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'LOWER(`a`)' );
        } );

        it( 'accepts a value', function() {
            $r = lower( val( 'Hello' ) )->toString( SQLType::MYSQL );
            expect( $r )->toBe( "LOWER('Hello')" );
        } );

        it( 'can have an alias', function() {
            $r = lower( 'a' )->as( 'foo' )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'LOWER(`a`) AS `foo`' );
        } );

        it( 'accepts another function as value', function() {
            $r = lower( concat( 'a', 'b' ) )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'LOWER(CONCAT(`a`, `b`))' );
        } );
    } );


    describe( 'substring', function() {

        it( 'accepts a field name', function() {
            $r = substring( 'a', 1, 3 )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'SUBSTRING(`a`, 1, 3)' );
        } );

        it( 'accepts a value', function() {
            $r = substring( val( 'Hello' ), 1, 3 )->toString( SQLType::MYSQL );
            expect( $r )->toBe( "SUBSTRING('Hello', 1, 3)" );
        } );

        it( 'can have an alias', function() {
            $r = substring( 'a', 1, 3 )->as( 'foo' )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'SUBSTRING(`a`, 1, 3) AS `foo`' );
        } );

        it( 'has the last argument optional', function() {
            $r = substring( 'a', 1 )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'SUBSTRING(`a`, 1)' );
        } );

        it( 'accepts another function as value', function() {
            $r = substring( concat( 'a', 'b' ), 1 )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'SUBSTRING(CONCAT(`a`, `b`), 1)' );
        } );
    } );



    describe( 'concat', function() {

        it( 'accepts field names', function() {
            $r = concat( 'a', 'b' )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'CONCAT(`a`, `b`)' );
        } );

        it( 'accepts a value', function() {
            $r = concat( val( 'Hello ' ), val( 'World' ) )->toString( SQLType::MYSQL );
            expect( $r )->toBe( "CONCAT('Hello ', 'World')" );
        } );

        it( 'can have an alias', function() {
            $r = concat( 'a', 'b' )->as( 'foo' )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'CONCAT(`a`, `b`) AS `foo`' );
        } );

        it( 'can receive three arguments', function() {
            $r = concat( 'a', 'b', 'c' )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'CONCAT(`a`, `b`, `c`)' );
        } );

        it( 'can receive more than tree arguments', function() {
            $r = concat( 'a', 'b', 'c', 'd', 'e' )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'CONCAT(`a`, `b`, `c`, `d`, `e`)' );
        } );

        it( 'accepts another function as first argument', function() {
            $r = concat( concat( 'a', 'b' ), 'c' )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'CONCAT(CONCAT(`a`, `b`), `c`)' );
        } );

        it( 'accepts another function as second argument', function() {
            $r = concat( 'a', concat( 'b' , 'c' ) )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'CONCAT(`a`, CONCAT(`b`, `c`))' );
        } );

        it( 'accepts another function as third argument', function() {
            $r = concat( 'a', 'b', concat( 'c' , 'd' ) )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'CONCAT(`a`, `b`, CONCAT(`c`, `d`))' );
        } );
    } );


    describe( 'length', function() {

        it( 'accepts a field name', function() {
            $r = length( 'a' )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'CHAR_LENGTH(`a`)' );
        } );

        it( 'accepts a value', function() {
            $r = length( val( 'Hello' ) )->toString( SQLType::MYSQL );
            expect( $r )->toBe( "CHAR_LENGTH('Hello')" );
        } );

        it( 'can have an alias', function() {
            $r = length( 'a' )->as( 'foo' )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'CHAR_LENGTH(`a`) AS `foo`' );
        } );

        it( 'accepts another function as value', function() {
            $r = length( concat( 'a', 'b' ) )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'CHAR_LENGTH(CONCAT(`a`, `b`))' );
        } );

    } );


    describe( 'bytes', function() {

        it( 'accepts a field name', function() {
            $r = bytes( 'a' )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'LENGTH(`a`)' );
        } );

        it( 'accepts a value', function() {
            $r = bytes( val( 'Hello' ) )->toString( SQLType::MYSQL );
            expect( $r )->toBe( "LENGTH('Hello')" );
        } );

        it( 'can have an alias', function() {
            $r = bytes( 'a' )->as( 'foo' )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'LENGTH(`a`) AS `foo`' );
        } );

        it( 'accepts another function as value', function() {
            $r = bytes( concat( 'a', 'b' ) )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'LENGTH(CONCAT(`a`, `b`))' );
        } );
    } );
} );