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

    } );


} );