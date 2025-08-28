<?php
namespace phputil\sql;

use DateTime;

require_once __DIR__ . '/../vendor/autoload.php';

describe( 'internal', function() {

    describe( '#__addApostropheIfNeeded', function() {

        it( 'returns TRUE for boolean true', function() {
            $r = __addApostropheIfNeeded( true );
            expect( $r )->toBe( 'TRUE' );
        } );

        it( 'returns FALSE for boolean false', function() {
            $r = __addApostropheIfNeeded( false );
            expect( $r )->toBe( 'FALSE' );
        } );

        describe( 'oracle database', function() {

            it( 'returns 1 for boolean true', function() {
                $r = __addApostropheIfNeeded( true, true );
                expect( $r )->toBe( '1' );
            } );

            it( 'returns 0 for boolean false', function() {
                $r = __addApostropheIfNeeded( false, true );
                expect( $r )->toBe( '0' );
            } );
        } );

        it( 'adds apostrophe to a string', function() {
            $r = __addApostropheIfNeeded( 'Hello' );
            expect( $r )->toBe( "'Hello'" );
        } );

        it( 'does not add apostrophe to a int value', function() {
            $r = __addApostropheIfNeeded( 50 );
            expect( $r )->toBe( '50' );
        } );

        it( 'does not add apostrophe to a float value', function() {
            $r = __addApostropheIfNeeded( 50.01 );
            expect( $r )->toBe( '50.01' );
        } );

        it( 'adds apostrophe to a DateTime value', function() {
            $v = '2020-01-01';
            $dt = new DateTime( $v );
            $r = __addApostropheIfNeeded( $dt );
            expect( $r )->toBe( "'$v'" );
        } );

        it( 'returns NULL for a null value', function() {
            $r = __addApostropheIfNeeded( null );
            expect( $r )->toBe( 'NULL' );
        } );

    } );


    describe( '#__booleanString', function() {

        it( 'converts true', function() {
            $r = __booleanString( true );
            expect( $r )->toBe( 'TRUE' );
        } );

        it( 'converts false', function() {
            $r = __booleanString( false );
            expect( $r )->toBe( 'FALSE' );
        } );

        it( 'can convert true to integer', function() {
            $r = __booleanString( true, true );
            expect( $r )->toBe( '1' );
        } );

        it( 'can convert false to integer', function() {
            $r = __booleanString( false, true );
            expect( $r )->toBe( '0' );
        } );


    } );

} );