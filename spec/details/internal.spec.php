<?php
namespace phputil\sql;

use \DateTime;

describe( 'internal', function() {

    describe( '#__getQuoteCharacters', function() {

        it( 'returns a pair of empty strings by default', function() {
            [ $first, $second ] = __getQuoteCharacters();
            expect( $first )->toBe( '' );
            expect( $second )->toBe( '' );
        } );

    } );

    describe( '#__addApostropheIfNeeded', function() {

        it( 'returns TRUE for boolean true', function() {
            $r = __toValue( true );
            expect( $r )->toBe( 'TRUE' );
        } );

        it( 'returns FALSE for boolean false', function() {
            $r = __toValue( false );
            expect( $r )->toBe( 'FALSE' );
        } );

        describe( 'oracle database', function() {

            it( 'returns 1 for boolean true', function() {
                $r = __toValue( true, SQLType::ORACLE );
                expect( $r )->toBe( '1' );
            } );

            it( 'returns 0 for boolean false', function() {
                $r = __toValue( false, SQLType::ORACLE );
                expect( $r )->toBe( '0' );
            } );
        } );

        it( 'adds apostrophe to a string', function() {
            $r = __toValue( 'Hello' );
            expect( $r )->toBe( "'Hello'" );
        } );

        it( 'does not add apostrophe to a int value', function() {
            $r = __toValue( 50 );
            expect( $r )->toBe( '50' );
        } );

        it( 'does not add apostrophe to a float value', function() {
            $r = __toValue( 50.01 );
            expect( $r )->toBe( '50.01' );
        } );

        it( 'adds apostrophe to a DateTime value', function() {
            $v = '2020-01-01';
            $dt = new DateTime( $v );
            $r = __toValue( $dt );
            expect( $r )->toBe( "'$v'" );
        } );

        it( 'returns NULL for a null value', function() {
            $r = __toValue( null );
            expect( $r )->toBe( 'NULL' );
        } );

    } );


    describe( '#__booleanString', function() {

        it( 'converts true', function() {
            $r = __toBoolean( true );
            expect( $r )->toBe( 'TRUE' );
        } );

        it( 'converts false', function() {
            $r = __toBoolean( false );
            expect( $r )->toBe( 'FALSE' );
        } );

        it( 'can convert true to integer', function() {
            $r = __toBoolean( true, true );
            expect( $r )->toBe( '1' );
        } );

        it( 'can convert false to integer', function() {
            $r = __toBoolean( false, true );
            expect( $r )->toBe( '0' );
        } );


    } );

} );