<?php
namespace phputil\sql;

describe( 'update', function() {

    it( 'accepts a table name', function() {
        $r = update( 'example' )->endAsString();
        expect( $r )->toBe( 'UPDATE example' );
    } );

    it( 'can convert to the target SQL/database type', function() {
        $r = update( 'example' )->endAsString( SQLType::MYSQL );
        expect( $r )->toBe( 'UPDATE `example`' );
    } );

    it( 'can have a where condition', function() {
        $r = update( 'example' )
            ->where( col( 'id' )->equalTo( 1 ) )
            ->endAsString( SQLType::MYSQL );
        expect( $r )->toBe( 'UPDATE `example` WHERE `id` = 1' );
    } );

    it( 'can be converted to string', function() {
        $r = update( 'example' )
            ->where( col( 'id' )->equalTo( 1 ) )
            ->end();

        expect( (string) $r )->toBe( 'UPDATE example WHERE id = 1' );
    } );

    it( 'can have set with attributions', function() {

        $r = update( 'example' )
            ->set( [ 'a' => 10, 'b' => 'b + 1', 'c' => 'c + c * 50/100', 'd' => "'Hello'" ])
            ->where( col( 'id' )->equalTo( 1 ) )
            ->endAsString( SQLType::MYSQL );

        expect( $r )->toBe(
            "UPDATE `example` SET `a` = 10, `b` = `b` + 1, `c` = `c` + `c` * 50/100, `d` = 'Hello' WHERE `id` = 1"
        );
    } );


    it( 'can have attribution with val', function() {

        $r = update( 'example' )
            ->set( [ 'a' => val( 10 ), 'd' => val( 'Hello' ) ])
            ->where( col( 'id' )->equalTo( 1 ) )
            ->endAsString( SQLType::MYSQL );

        expect( $r )->toBe(
            "UPDATE `example` SET `a` = 10, `d` = 'Hello' WHERE `id` = 1"
        );
    } );


    // it( 'can receive anonymous params', function() {

    //     $r = update( 'example' )
    //         ->set( [ 'a' => param(), 'b' => param() ])
    //         ->where( col( 'id' )->equalTo( 1 ) )
    //         ->endAsString( SQLType::MYSQL );

    //     expect( $r )->toBe(
    //         "UPDATE `example` SET `a` = ?, `b` = ? WHERE `id` = 1"
    //     );
    // } );

} );