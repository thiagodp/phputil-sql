<?php
namespace phputil\sql;

describe( 'aggregate with the class Func', function() {

    it( 'accepts asterisk', function() {
        $r = count( '*' )->toString();
        expect( $r )->toBe( "COUNT(*)" );
    } );

    it( 'accepts a column name', function() {
        $r = count( col('a') )->toString( DBType::MYSQL );
        expect( $r )->toBe( "COUNT(`a`)" );
    } );

    it( 'accepts a column name by default', function() {
        $r = count( 'a' )->toString( DBType::MYSQL );
        expect( $r )->toBe( "COUNT(`a`)" );
    } );

    it( 'accepts an alias as parameter', function() {
        $r = count( 'long', 'l' )->toString( DBType::MYSQL );
        expect( $r )->toBe( "COUNT(`long`) AS `l`" );
    } );

    it( 'accepts an alias as build method', function() {
        $r = count( 'long' )->as( 'l' )->toString( DBType::MYSQL );
        expect( $r )->toBe( "COUNT(`long`) AS `l`" );
    } );

    it( 'has now()', function() {
        $r = now()->toString( DBType::MYSQL );
        expect( $r )->toBe( 'NOW()' );
    } );

    it( 'has date()', function() {
        $r = date()->toString( DBType::MYSQL );
        expect( $r )->toBe( 'CURRENT_DATE' );
    } );

    it( 'has time()', function() {
        $r = time()->toString( DBType::MYSQL );
        expect( $r )->toBe( 'CURRENT_TIME' );
    } );

    describe( 'extract()', function() {

        it( 'can be called with a unit and a column', function() {
            $r = extract( Extract::DAY, 'birth' )->toString( DBType::MYSQL );
            expect( $r )->toBe( 'EXTRACT(DAY FROM `birth`)' );
        } );

        it( 'can be called with a unit and a date value', function() {
            $r = extract( Extract::DAY, val( '2025/01/31' ) )->toString( DBType::MYSQL );
            expect( $r )->toBe( "EXTRACT(DAY FROM '2025/01/31')" );
        } );

        it( 'can be called with a unit a declare "from" later', function() {
            $r = extract( Extract::DAY )->from( val( '2025/01/31' ) )->toString( DBType::MYSQL );
            expect( $r )->toBe( "EXTRACT(DAY FROM '2025/01/31')" );
        } );

        it( 'when called without "from" it receive an empty value', function() {
            $r = extract( Extract::DAY )->toString( DBType::MYSQL );
            expect( $r )->toBe( "EXTRACT(DAY FROM '')" );
        } );

    } );

} );