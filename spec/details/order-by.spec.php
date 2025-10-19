<?php
namespace phputil\sql;

describe( 'orderBy', function() {

    it( 'order ASC by default', function() {
        $r = select()->from( 'foo' )->orderBy( 'a' )->endAsString();
        expect( $r )->toBe( 'SELECT * FROM foo ORDER BY a ASC' );
    } );

    it( 'order with more than one field', function() {
        $r = select()->from( 'foo' )->orderBy( 'a', 'b' )->endAsString();
        expect( $r )->toBe( 'SELECT * FROM foo ORDER BY a ASC, b ASC' );
    } );

    it( 'can order DESC', function() {
        $r = select()->from( 'foo' )->orderBy( 'a DESC' )->endAsString();
        expect( $r )->toBe( 'SELECT * FROM foo ORDER BY a DESC' );
    } );

    it( 'can order DESC with a function', function() {
        $r = select()->from( 'foo' )->orderBy( desc( 'a' ) )->endAsString();
        expect( $r )->toBe( 'SELECT * FROM foo ORDER BY a DESC' );
    } );

    it( 'can order ASC with a function', function() {
        $r = select()->from( 'foo' )->orderBy( asc( 'a' ) )->endAsString();
        expect( $r )->toBe( 'SELECT * FROM foo ORDER BY a ASC' );
    } );

    it( 'can mix ASC and DESC fields', function() {
        $r = select()->from( 'foo' )->orderBy( desc( 'a' ), asc( 'b' ), desc( 'c' )  )->endAsString();
        expect( $r )->toBe( 'SELECT * FROM foo ORDER BY a DESC, b ASC, c DESC' );
    } );

    it( 'can use an aggregate function', function() {
        $r = select()->from( 'foo' )->orderBy( count('id') )->endAsString( SQLType::MYSQL );
        expect( $r )->toBe( 'SELECT * FROM `foo` ORDER BY COUNT(`id`) ASC' );
    } );
} );