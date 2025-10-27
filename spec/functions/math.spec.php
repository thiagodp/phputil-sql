<?php
namespace phputil\sql;

describe( 'math functions', function() {

    describe( 'abs', function() {

        it( 'accepts a field name', function() {
            $r = abs( 'a' )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'ABS(`a`)' );
        } );

        it( 'accepts a value', function() {
            $r = abs( val( 10 ) )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'ABS(10)' );
        } );

        it( 'accepts an expression with fields', function() {
            $r = abs( 'a + b * c' )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'ABS(`a` + `b` * `c`)' );
        } );

        it( 'can have an alias', function() {
            $r = abs( 'a' )->as( 'foo' )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'ABS(`a`) AS `foo`' );
        } );

    } );


    describe( 'round', function() {

        it( 'accepts a field name', function() {
            $r = round( 'a' )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'ROUND(`a`)' );
        } );

        it( 'accepts a value', function() {
            $r = round( val( 10 ) )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'ROUND(10)' );
        } );

        it( 'accepts an expression with fields', function() {
            $r = round( 'a + b * c' )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'ROUND(`a` + `b` * `c`)' );
        } );

        it( 'can have an alias', function() {
            $r = round( 'a' )->as( 'foo' )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'ROUND(`a`) AS `foo`' );
        } );

        describe( 'with precision', function() {

            it( 'accepts a field name', function() {
                $r = round( 'a', 2 )->toString( SQLType::MYSQL );
                expect( $r )->toBe( 'ROUND(`a`, 2)' );
            } );

            it( 'accepts a value', function() {
                $r = round( val( 10 ), 2 )->toString( SQLType::MYSQL );
                expect( $r )->toBe( 'ROUND(10, 2)' );
            } );

            it( 'accepts an expression with fields', function() {
                $r = round( 'a + b * c', 2 )->toString( SQLType::MYSQL );
                expect( $r )->toBe( 'ROUND(`a` + `b` * `c`, 2)' );
            } );

            it( 'can have an alias', function() {
                $r = round( 'a', 2 )->as( 'foo' )->toString( SQLType::MYSQL );
                expect( $r )->toBe( 'ROUND(`a`, 2) AS `foo`' );
            } );

            it( 'accepts another function as value', function() {
                $r = round( tan( 'a' ), 2 )->toString( SQLType::MYSQL );
                expect( $r )->toBe( 'ROUND(TAN(`a`), 2)' );
            } );

            it( 'accepts chained functions', function() {
                $r = round( cos( tan( 'a' ) ), 2 )->toString( SQLType::MYSQL );
                expect( $r )->toBe( 'ROUND(COS(TAN(`a`)), 2)' );
            } );

        } );

    } );


    describe( 'ceil', function() {

        it( 'accepts a field name', function() {
            $r = ceil( 'a' )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'CEIL(`a`)' );
        } );

        it( 'accepts a value', function() {
            $r = ceil( val( 10 ) )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'CEIL(10)' );
        } );

        it( 'accepts an expression with fields', function() {
            $r = ceil( 'a + b * c' )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'CEIL(`a` + `b` * `c`)' );
        } );

        it( 'can have an alias', function() {
            $r = ceil( 'a' )->as( 'foo' )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'CEIL(`a`) AS `foo`' );
        } );

        it( 'accepts another function as value', function() {
            $r = ceil( tan( 'a' ) )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'CEIL(TAN(`a`))' );
        } );

    } );


    describe( 'floor', function() {

        it( 'accepts a field name', function() {
            $r = floor( 'a' )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'FLOOR(`a`)' );
        } );

        it( 'accepts a value', function() {
            $r = floor( val( 10 ) )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'FLOOR(10)' );
        } );

        it( 'accepts an expression with fields', function() {
            $r = floor( 'a + b * c' )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'FLOOR(`a` + `b` * `c`)' );
        } );

        it( 'can have an alias', function() {
            $r = floor( 'a' )->as( 'foo' )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'FLOOR(`a`) AS `foo`' );
        } );

        it( 'accepts another function as value', function() {
            $r = floor( tan( 'a' ) )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'FLOOR(TAN(`a`))' );
        } );
    } );


    describe( 'power', function() {

        it( 'accepts field names', function() {
            $r = power( 'a', 'b' )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'POWER(`a`, `b`)' );
        } );

        it( 'accepts values', function() {
            $r = power( val( 10 ), val( 2 ) )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'POWER(10, 2)' );
        } );

        it( 'accepts expressions with fields', function() {
            $r = power( 'a + b * c', 'd - e' )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'POWER(`a` + `b` * `c`, `d` - `e`)' );
        } );

        it( 'can have an alias', function() {
            $r = power( 'a', 2 )->as( 'foo' )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'POWER(`a`, 2) AS `foo`' );
        } );

        it( 'accepts a function as base', function() {
            $r = power( tan( 'a' ), 2 )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'POWER(TAN(`a`), 2)' );
        } );

        it( 'accepts a function as exponent', function() {
            $r = power( 2, floor( 2.3 ) )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'POWER(2, FLOOR(2.3))' );
        } );
    } );


    describe( 'sqrt', function() {

        it( 'accepts a field name', function() {
            $r = sqrt( 'a' )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'SQRT(`a`)' );
        } );

        it( 'accepts a value', function() {
            $r = sqrt( val( 10 ) )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'SQRT(10)' );
        } );

        it( 'accepts an expression with fields', function() {
            $r = sqrt( 'a + b * c' )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'SQRT(`a` + `b` * `c`)' );
        } );

        it( 'can have an alias', function() {
            $r = sqrt( 'a' )->as( 'foo' )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'SQRT(`a`) AS `foo`' );
        } );

        it( 'accepts another function as value', function() {
            $r = sqrt( floor( 2.3 ) )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'SQRT(FLOOR(2.3))' );
        } );
    } );


    describe( 'sin', function() {

        it( 'accepts a field name', function() {
            $r = sin( 'a' )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'SIN(`a`)' );
        } );

        it( 'accepts a value', function() {
            $r = sin( val( 10 ) )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'SIN(10)' );
        } );

        it( 'accepts an expression with fields', function() {
            $r = sin( 'a + b * c' )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'SIN(`a` + `b` * `c`)' );
        } );

        it( 'can have an alias', function() {
            $r = sin( 'a' )->as( 'foo' )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'SIN(`a`) AS `foo`' );
        } );

        it( 'accepts another function as value', function() {
            $r = sin( cos( 'a' ) )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'SIN(COS(`a`))' );
        } );
    } );


    describe( 'cos', function() {

        it( 'accepts a field name', function() {
            $r = cos( 'a' )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'COS(`a`)' );
        } );

        it( 'accepts a value', function() {
            $r = cos( val( 10 ) )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'COS(10)' );
        } );

        it( 'accepts an expression with fields', function() {
            $r = cos( 'a + b * c' )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'COS(`a` + `b` * `c`)' );
        } );

        it( 'can have an alias', function() {
            $r = cos( 'a' )->as( 'foo' )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'COS(`a`) AS `foo`' );
        } );

        it( 'accepts another function as value', function() {
            $r = cos( sin( 'a' ) )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'COS(SIN(`a`))' );
        } );
    } );


    describe( 'tan', function() {

        it( 'accepts a field name', function() {
            $r = tan( 'a' )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'TAN(`a`)' );
        } );

        it( 'accepts a value', function() {
            $r = tan( val( 10 ) )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'TAN(10)' );
        } );

        it( 'accepts an expression with fields', function() {
            $r = tan( 'a + b * c' )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'TAN(`a` + `b` * `c`)' );
        } );

        it( 'can have an alias', function() {
            $r = tan( 'a' )->as( 'foo' )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'TAN(`a`) AS `foo`' );
        } );

        it( 'accepts another function as value', function() {
            $r = tan( cos( 'a' ) )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'TAN(COS(`a`))' );
        } );

    } );

} );