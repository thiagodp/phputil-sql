<?php
namespace phputil\sql;

describe( 'select', function() {

    it( 'can select a number', function() {
        $r = select( 1 )->toString();
        expect( $r )->toBe( 'SELECT 1' );
    } );

    it( 'can select numbers', function() {
        $r = select( 1, 2, 3 )->toString();
        expect( $r )->toBe( 'SELECT 1, 2, 3' );
    } );

    it( 'can select fields', function() {
        $r = select( 'one', 'two', 'three' )->toString();
        expect( $r )->toBe( 'SELECT one, two, three' );
    } );

    it( 'can select fields with aliases', function() {
        $r = select( 'one AS a', 'two AS b', 'three AS c' )->toString();
        expect( $r )->toBe( 'SELECT one AS a, two AS b, three AS c' );
    } );

    it( 'applies quotes to fields with aliases', function() {
        $r = select( 'one AS a', 'two AS b', 'three AS c' )->toString( SQLType::MYSQL );
        expect( $r )->toBe( 'SELECT `one` AS `a`, `two` AS `b`, `three` AS `c`' );
    } );

    it( 'applies quotes to fields with table and their aliases', function() {
        $r = select( 'x.one AS a', 'y.two AS b', 'z.three AS c' )->toString( SQLType::MYSQL );
        expect( $r )->toBe( 'SELECT `x`.`one` AS `a`, `y`.`two` AS `b`, `z`.`three` AS `c`' );
    } );

    it( 'can select fields with table name', function() {
        $r = select( 'a.one', 'b.two', 'c.three' )->toString();
        expect( $r )->toBe( 'SELECT a.one, b.two, c.three' );
    } );

    it( 'applies quotes to fields and table names', function() {
        $r = select( 'a.one', 'b.two', 'c.three' )->toString( SQLType::MYSQL );
        expect( $r )->toBe( 'SELECT `a`.`one`, `b`.`two`, `c`.`three`' );
    } );

    it( 'can select with a star', function() {
        $r = select( '*' )->toString();
        expect( $r )->toBe( 'SELECT *' );
    } );

    it( 'can select with a star when no arguments are given', function() {
        $r = select()->toString();
        expect( $r )->toBe( 'SELECT *' );
    } );

    it( 'can select with aggregate functions', function() {
        $r = select( count( 'id' ), max( 'id' ), min( 'id' ) )->toString( SQLType::MYSQL );
        expect( $r )->toBe( 'SELECT COUNT(`id`), MAX(`id`), MIN(`id`)' );
    } );

    it( 'can select with date and time functions', function() {
        $sql = select( now(), date(), time() )->toString( SQLType::MYSQL );
        expect( $sql )->toBe( 'SELECT NOW(), CURRENT_DATE, CURRENT_TIME' );
    } );

    it( 'can select with mixed content', function() {
        $r = select( 1, 'one', 'a.*', count('id')  )->toString();
        expect( $r )->toBe( 'SELECT 1, one, a.*, COUNT(id)' );
    } );


} );