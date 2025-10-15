<?php
namespace phputil\sql;

describe( 'conditions', function() {

    it( 'converts equalTo()', function() {
        $r = col( 'x' )->equalTo( 1 )->toString();
        expect( $r )->toBe( 'x = 1' );
    } );

    it( 'converts notEqualTo()', function() {
        $r = col( 'x' )->notEqualTo( 1 )->toString();
        expect( $r )->toBe( 'x <> 1' );
    } );

    it( 'converts differentFrom()', function() {
        $r = col( 'x' )->differentFrom( 1 )->toString();
        expect( $r )->toBe( 'x <> 1' );
    } );

    it( 'converts greaterThan()', function() {
        $r = col( 'x' )->greaterThan( 1 )->toString();
        expect( $r )->toBe( 'x > 1' );
    } );

    it( 'converts greaterThanOrEqualTo()', function() {
        $r = col( 'x' )->greaterThanOrEqualTo( 1 )->toString();
        expect( $r )->toBe( 'x >= 1' );
    } );

    it( 'converts lessThan()', function() {
        $r = col( 'x' )->lessThan( 1 )->toString();
        expect( $r )->toBe( 'x < 1' );
    } );

    it( 'converts lessThanOrEqualTo()', function() {
        $r = col( 'x' )->lessThanOrEqualTo( 1 )->toString();
        expect( $r )->toBe( 'x <= 1' );
    } );

    it( 'converts like()', function() {
        $r = col( 'x' )->like( 'A' )->toString();
        expect( $r )->toBe( "x LIKE 'A'" );
    } );

    it( 'converts startWith()', function() {
        $r = col( 'x' )->startWith( 'A' )->toString();
        expect( $r )->toBe( "x LIKE 'A%'" );
    } );

    it( 'converts endWith()', function() {
        $r = col( 'x' )->endWith( 'A' )->toString();
        expect( $r )->toBe( "x LIKE '%A'" );
    } );

    it( 'converts contain()', function() {
        $r = col( 'x' )->contain( 'A' )->toString();
        expect( $r )->toBe( "x LIKE '%A%'" );
    } );

    it( 'converts between()', function() {
        $r = col( 'x' )->between( 'A', 'Z' )->toString();
        expect( $r )->toBe( "x BETWEEN 'A' AND 'Z'" );
    } );

    it( 'converts in() with an array', function() {
        $r = col( 'x' )->in( [ 'A', 'B' ] )->toString();
        expect( $r )->toBe( "x IN ('A', 'B')" );
    } );

    it( 'converts in() with a sub select', function() {
        $r = col( 'x' )->in( select( 'y' )->from( 'foo' ) )->toString();
        expect( $r )->toBe( "x IN (SELECT y FROM foo)" );
    } );

    it( 'converts isNull()', function() {
        $r = col( 'x' )->isNull()->toString();
        expect( $r )->toBe( "x IS NULL" );
    } );

    it( 'converts isNotNull()', function() {
        $r = col( 'x' )->isNotNull()->toString();
        expect( $r )->toBe( "x IS NOT NULL" );
    } );

    it( 'converts isTrue()', function() {
        $r = col( 'x' )->isTrue()->toString();
        expect( $r )->toBe( "x IS TRUE" );
    } );

    it( 'converts isFalse()', function() {
        $r = col( 'x' )->isFalse()->toString();
        expect( $r )->toBe( "x IS FALSE" );
    } );


    describe( 'right-side values', function() {

        it( 'must not put quotes into an int value', function() {
            $r = col( 'x' )->equalTo( 1 )->toString();
            expect( $r )->toBe( 'x = 1' );
        } );

        it( 'must not put quotes into a float value', function() {
            $r = col( 'x' )->equalTo( 1.01 )->toString();
            expect( $r )->toBe( 'x = 1.01' );
        } );

        it( 'must not put quotes into a boolean value', function() {
            $r = col( 'x' )->equalTo( true )->toString();
            expect( $r )->toBe( 'x = TRUE' );
            $r = col( 'x' )->equalTo( false )->toString();
            expect( $r )->toBe( 'x = FALSE' );
        } );

        it( 'must convert a null value', function() {
            $r = col( 'x' )->equalTo( null )->toString();
            expect( $r )->toBe( 'x = NULL' );
        } );

        it( 'must put quotes into a string value', function() {
            $r = col( 'x' )->equalTo( 'A' )->toString();
            expect( $r )->toBe( "x = 'A'" );
        } );

        it( 'must convert a DateTime value', function() {
            $expected = '2025-12-31';
            $date = new \DateTime( $expected );
            $r = col( 'x' )->equalTo( $date )->toString();
            expect( $r )->toBe( "x = '$expected'" );
        } );

        it( 'must convert a DateTime value in a BETWEEN comparison', function() {
            $min = '2025-12-31';
            $max = '2026-12-31';
            $minDate = new \DateTime( $min );
            $maxDate = new \DateTime( $max );
            $r = col( 'x' )->between( $minDate, $maxDate )->toString();
            expect( $r )->toBe( "x BETWEEN '$min' AND '$max'" );
        } );

        it( 'must convert different array values for an IN expression', function() {
            $dateStr = '2025-12-31';
            $date = new \DateTime( $dateStr );
            $r = col( 'x' )->in( [ true, false, 10, 3.14, 'A', $date ] )->toString();
            expect( $r )->toBe( "x IN (TRUE, FALSE, 10, 3.14, 'A', '$dateStr')" );
        } );

    } );


    describe( 'not', function() {

        it( 'accepts a condition', function() {
            $r = not( col( 'a' )->equalTo( 10 ) )->toString();
            expect( $r )->toBe( 'NOT (a = 10)' );
        } );

    } );

} );