<?php
namespace phputil\sql;

describe( 'logic utilities', function() {

    describe( 'andAll', function() {

        it( 'converts correctly', function() {
            $c1 = col( 'a' )->equalTo( 10 );
            $c2 = col( 'b' )->equalTo( 20 );
            $c3 = col( 'c' )->equalTo( 30 );
            $r = andAll( $c1, $c2, $c3 )->toString();
            expect( $r )->toBe( 'a = 10 AND b = 20 AND c = 30' );
        } );

    } );


    describe( 'orAll', function() {

        it( 'converts correctly', function() {
            $c1 = col( 'a' )->equalTo( 10 );
            $c2 = col( 'b' )->equalTo( 20 );
            $c3 = col( 'c' )->equalTo( 30 );
            $r = orAll( $c1, $c2, $c3 )->toString();
            expect( $r )->toBe( 'a = 10 OR b = 20 OR c = 30' );
        } );

    } );
} );