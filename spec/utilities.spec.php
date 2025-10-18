<?php
namespace phputil\sql;

describe( 'utilities', function() {

    describe( 'col', function() {

        it( 'can have an alias with the as() method', function() {
            $r = col( 'long' )->as( 'l' )->toString( SQLType::MYSQL );
            expect( $r )->toBe( '`long` AS `l`' );
        } );

        it( 'can have an alias directly in the column name', function() {
            $r = col( 'long AS l' )->toString( SQLType::MYSQL );
            expect( $r )->toBe( '`long` AS `l`' );
        } );

        it( 'does not add an alias with the as() method if already given', function() {
            $r = col( 'long AS l' )->as( 'z' )->toString( SQLType::MYSQL );
            expect( $r )->toBe( '`long` AS `l`' );
        } );

    } );

    describe( 'param', function() {

        it( 'returns a question mark when called without an argument', function() {
            // $r = param()->toString();
            $r = param();
            expect( (string) $r )->toBe( '?' );
        } );

        it( 'returns a question mark when called with an empty string', function() {
            // $r = param( '' )->toString();
            $r = param( '' );
            expect( (string) $r )->toBe( '?' );
        } );

        it( 'returns a question mark when called with colon', function() {
            // $r = param( ':' )->toString();
            $r = param( ':' );
            expect( (string) $r )->toBe( '?' );
        } );

        it( 'returns the given parameter with colon', function() {
            // $r = param( 'x' )->toString();
            $r = param( 'x' );
            expect( (string) $r )->toBe( ':x' );
        } );

        it( 'returns the given parameter with just one colon, when the parameter already has one colon', function() {
            // $r = param( ':x' )->toString();
            $r = param( ':x' );
            expect( (string) $r )->toBe( ':x' );
        } );

    } );


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