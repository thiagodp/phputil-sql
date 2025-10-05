<?php
namespace phputil\sql;

require_once __DIR__ . '/../vendor/autoload.php';

describe( 'utilities', function() {

    describe( 'col', function() {

        it( 'can have an alias with the as() method', function() {
            $r = col( 'long' )->as( 'l' )->toString( DBType::MYSQL );
            expect( $r )->toBe( '`long` AS `l`' );
        } );

        it( 'can have an alias directly in the column name', function() {
            $r = col( 'long AS l' )->toString( DBType::MYSQL );
            expect( $r )->toBe( '`long` AS `l`' );
        } );

        it( 'does not add an alias with the as() method if already given', function() {
            $r = col( 'long AS l' )->as( 'z' )->toString( DBType::MYSQL );
            expect( $r )->toBe( '`long` AS `l`' );
        } );

    } );

    describe( 'param', function() {

        it( 'returns a question mark when called without an argument', function() {
            $r = param()->toString();
            expect( $r )->toBe( '?' );
        } );

        it( 'returns a question mark when called with an empty string', function() {
            $r = param( '' )->toString();
            expect( $r )->toBe( '?' );
        } );

        it( 'returns a question mark when called with colon', function() {
            $r = param( ':' )->toString();
            expect( $r )->toBe( '?' );
        } );

        it( 'returns the given parameter with colon', function() {
            $r = param( 'x' )->toString();
            expect( $r )->toBe( ':x' );
        } );

        it( 'returns the given parameter with just one colon, when the parameter already has one colon', function() {
            $r = param( ':x' )->toString();
            expect( $r )->toBe( ':x' );
        } );

    } );

} );