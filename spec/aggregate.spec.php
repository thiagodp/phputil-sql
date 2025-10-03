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
        $r = count( 'long' )->alias( 'l' )->toString( DBType::MYSQL );
        expect( $r )->toBe( "COUNT(`long`) AS `l`" );
    } );
} );