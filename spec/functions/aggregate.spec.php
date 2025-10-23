<?php
namespace phputil\sql;

describe( 'aggregate functions', function() {

    it( 'accepts asterisk', function() {
        $r = count( '*' )->toString();
        expect( $r )->toBe( "COUNT(*)" );
    } );

    it( 'accepts a column name', function() {
        $r = count( col('a') )->toString( SQLType::MYSQL );
        expect( $r )->toBe( "COUNT(`a`)" );
    } );

    it( 'accepts a column name by default', function() {
        $r = count( 'a' )->toString( SQLType::MYSQL );
        expect( $r )->toBe( "COUNT(`a`)" );
    } );

    it( 'accepts an alias as parameter', function() {
        $r = count( 'long', 'l' )->toString( SQLType::MYSQL );
        expect( $r )->toBe( "COUNT(`long`) AS `l`" );
    } );

    it( 'accepts an alias as build method', function() {
        $r = count( 'long' )->as( 'l' )->toString( SQLType::MYSQL );
        expect( $r )->toBe( "COUNT(`long`) AS `l`" );
    } );

    it( 'accepts a calculus in the column name', function() {
        $r = count( 'a * b' )->toString( SQLType::MYSQL );
        expect( $r )->toBe( "COUNT(`a` * `b`)" );
    } );

    it( 'accepts a longer calculus in the column name', function() {
        $r = count( 'a * b + c - d / e' )->toString( SQLType::MYSQL );
        expect( $r )->toBe( "COUNT(`a` * `b` + `c` - `d` / `e`)" );
    } );

    it( 'accepts calculus with parenthesis', function() {
        $r = count( 'a * (b + c) - (d / e)' )->toString( SQLType::MYSQL );
        expect( $r )->toBe( "COUNT(`a` * (`b` + `c`) - (`d` / `e`))" );
    } );

    it( 'accepts calculus with parenthesis when names have backticks or quotes', function() {
        $r = count( '`a` * (`b` + `c`) - (`d` / `e`)' )->toString( SQLType::MYSQL );
        expect( $r )->toBe( "COUNT(`a` * (`b` + `c`) - (`d` / `e`))" );
    } );

    it( 'accepts calculus with parenthesis and table names', function() {
        $r = count( 't1.a * (t1.b + t1.c) - (t2.d / t2.e)' )->toString( SQLType::MYSQL );
        expect( $r )->toBe( "COUNT(`t1`.`a` * (`t1`.`b` + `t1`.`c`) - (`t2`.`d` / `t2`.`e`))" );
    } );

} );