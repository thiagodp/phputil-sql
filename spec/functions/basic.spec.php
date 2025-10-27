<?php
namespace phputil\sql;

use DateTime;

describe( 'basic utilities', function() {

    describe( 'val', function() {

        it( 'can receive an integer', function() {
            $r = val( 1 )->equalTo( 1 )->toString();
            expect( $r )->toBe( '1 = 1' );
        } );

        it( 'can receive a float', function() {
            $r = val( 1.2 )->notEqualTo( 1 )->toString();
            expect( $r )->toBe( '1.2 <> 1' );
        } );

        it( 'can receive a string', function() {
            $r = val( 'a' )->notEqualTo( 'b' )->toString();
            expect( $r )->toBe( "'a' <> 'b'" );
        } );

        it( 'can receive a bool', function() {
            $r = val( false )->notEqualTo( true )->toString();
            expect( $r )->toBe( "FALSE <> TRUE" );
        } );

        it( 'can receive null', function() {
            $r = val( null )->notEqualTo( false )->toString();
            expect( $r )->toBe( "NULL <> FALSE" );
        } );

        it( 'can receive a date', function() {
            $r = val( new DateTime( '2024-12-31' ) )->notEqualTo( null )->toString();
            expect( $r )->toBe( "'2024-12-31' <> NULL" );
        } );

        it( 'can receive a function', function() {
            $r = val( count('*') )->equalTo( 1 )->toString();
            expect( $r )->toBe( 'COUNT(*) = 1' );
        } );

    } );


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
            $r = (string) param();
            expect( $r )->toBe( '?' );
        } );

        it( 'returns a question mark when called with an empty string', function() {
            // $r = param( '' )->toString();
            $r = (string) param( '' );
            expect( $r )->toBe( '?' );
        } );

        it( 'returns a question mark when called with colon', function() {
            // $r = param( ':' )->toString();
            $r = (string) param( ':' );
            expect( $r )->toBe( '?' );
        } );

        it( 'returns the given parameter with colon', function() {
            // $r = param( 'x' )->toString();
            $r = (string) param( 'x' );
            expect( $r )->toBe( ':x' );
        } );

        it( 'returns the given parameter with just one colon, when the parameter already has one colon', function() {
            // $r = param( ':x' )->toString();
            $r = (string) param( ':x' );
            expect( $r )->toBe( ':x' );
        } );

    } );

} );