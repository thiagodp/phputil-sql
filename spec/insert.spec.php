<?php
namespace phputil\sql;

describe( 'insertInto', function() {

    it( 'accepts a table name', function() {
        $r = insertInto( 'example' )->endAsString();
        expect( $r )->toBe( 'INSERT INTO example' );
    } );

    it( 'can convert to the target SQL/database type', function() {
        $r = insertInto( 'example' )->endAsString( SQLType::MYSQL );
        expect( $r )->toBe( 'INSERT INTO `example`' );
    } );

    it( 'can have columns', function() {
        $r = insertInto( 'example', [ 'one', 'two' ] )
            ->endAsString( SQLType::MYSQL );
        expect( $r )->toBe( 'INSERT INTO `example` (`one`, `two`)' );
    } );

    it( 'can have values', function() {
        $r = insertInto( 'example', [ 'one', 'two' ] )
            ->values( [ 1, 'hello' ] )
            ->endAsString( SQLType::MYSQL );
        expect( $r )->toBe(
            "INSERT INTO `example` (`one`, `two`) VALUES (1, 'hello')"
        );
    } );

    it( 'can have more than one value', function() {

        $r = insertInto( 'example', [ 'one', 'two' ] )
            ->values(
                [ 1, 'hello' ],
                [ 2, 'world' ],
            )->endAsString( SQLType::MYSQL );

        expect( $r )->toBe(
            "INSERT INTO `example` (`one`, `two`) VALUES (1, 'hello'), (2, 'world')"
        );
    } );


    it( 'can have a select', function() {

        $r = insertInto( 'example', [ 'one', 'two' ],
                select( 'a', 'b' )->from( 'other' )->end()
            )->endAsString( SQLType::MYSQL );

        expect( $r )->toBe(
            "INSERT INTO `example` (`one`, `two`) SELECT `a`, `b` FROM `other`"
        );
    } );


    it( 'can be converted to string', function() {

        $r = insertInto( 'example', [ 'one', 'two' ],
                select( 'a', 'b' )->from( 'other' )->end()
            )->end();

        expect( (string) $r )->toBe(
            "INSERT INTO example (one, two) SELECT a, b FROM other"
        );
    } );


} );