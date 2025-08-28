<?php
namespace phputil\sql;

describe( 'conditions', function() {

    it( 'converts equalTo', function() {
        $r = (string) col( 'x' )->equalTo( 1 );
        expect( $r )->toBe( 'x = 1' );
    } );

    it( 'converts notEqualTo', function() {
        $r = (string) col( 'x' )->notEqualTo( 1 );
        expect( $r )->toBe( 'x <> 1' );
    } );

    it( 'converts differentFrom', function() {
        $r = (string) col( 'x' )->differentFrom( 1 );
        expect( $r )->toBe( 'x <> 1' );
    } );

    it( 'converts greaterThan', function() {
        $r = (string) col( 'x' )->greaterThan( 1 );
        expect( $r )->toBe( 'x > 1' );
    } );

    it( 'converts greaterThanOrEqualTo', function() {
        $r = (string) col( 'x' )->greaterThanOrEqualTo( 1 );
        expect( $r )->toBe( 'x >= 1' );
    } );

    it( 'converts lessThan', function() {
        $r = (string) col( 'x' )->lessThan( 1 );
        expect( $r )->toBe( 'x < 1' );
    } );

    it( 'converts lessThanOrEqualTo', function() {
        $r = (string) col( 'x' )->lessThanOrEqualTo( 1 );
        expect( $r )->toBe( 'x <= 1' );
    } );

    it( 'converts like', function() {
        $r = (string) col( 'x' )->like( 'A' );
        expect( $r )->toBe( "x LIKE 'A'" );
    } );

    it( 'converts startWith', function() {
        $r = (string) col( 'x' )->startWith( 'A' );
        expect( $r )->toBe( "x LIKE 'A%'" );
    } );

    it( 'converts endWith', function() {
        $r = (string) col( 'x' )->endWith( 'A' );
        expect( $r )->toBe( "x LIKE '%A'" );
    } );

    it( 'converts contain', function() {
        $r = (string) col( 'x' )->contain( 'A' );
        expect( $r )->toBe( "x LIKE '%A%'" );
    } );

    it( 'converts between', function() {
        $r = (string) col( 'x' )->between( 'A', 'Z' );
        expect( $r )->toBe( "x BETWEEN 'A' AND 'Z'" );
    } );


    describe( 'right-side values', function() {

        it( 'must not put quotes into an int value', function() {
            $r = (string) col( 'x' )->equalTo( 1 );
            expect( $r )->toBe( 'x = 1' );
        } );

        it( 'must not put quotes into a float value', function() {
            $r = (string) col( 'x' )->equalTo( 1.01 );
            expect( $r )->toBe( 'x = 1.01' );
        } );

        it( 'must not put quotes into a boolean value', function() {
            $r = (string) col( 'x' )->equalTo( true );
            expect( $r )->toBe( 'x = TRUE' );
            $r = (string) col( 'x' )->equalTo( false );
            expect( $r )->toBe( 'x = FALSE' );
        } );

        it( 'must convert a null value', function() {
            $r = (string) col( 'x' )->equalTo( null );
            expect( $r )->toBe( 'x = NULL' );
        } );

        it( 'must put quotes into a string value', function() {
            $r = (string) col( 'x' )->equalTo('A' );
            expect( $r )->toBe( "x = 'A'" );
        } );

        it( 'must convert a DateTime value', function() {
            $expected = '2025-12-31';
            $date = new \DateTime( $expected );
            $r = (string) col( 'x' )->equalTo( $date );
            expect( $r )->toBe( "x = '$expected'" );
        } );

        it( 'must convert a DateTime value in a BETWEEN comparison', function() {
            $min = '2025-12-31';
            $max = '2026-12-31';
            $minDate = new \DateTime( $min );
            $maxDate = new \DateTime( $max );
            $r = (string) col( 'x' )->between( $minDate, $maxDate );
            expect( $r )->toBe( "x BETWEEN '$min' AND '$max'" );
        } );

        it( 'must convert different array values for an IN expression', function() {
            $dateStr = '2025-12-31';
            $date = new \DateTime( $dateStr );
            $r = (string) col( 'x' )->in( [ true, false, 10, 3.14, 'A', $date ] );
            expect( $r )->toBe( "x IN (TRUE, FALSE, 10, 3.14, 'A', '$dateStr')" );
        } );

    } );

} );