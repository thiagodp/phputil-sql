<?php
namespace phputil\sql;

describe( 'deleteFrom', function() {

    it( 'accepts a table name', function() {
        $r = deleteFrom( 'example' )->endAsString();
        expect( $r )->toBe( 'DELETE FROM example' );
    } );

    it( 'can convert to the target SQL/database type', function() {
        $r = deleteFrom( 'example' )->endAsString( SQLType::MYSQL );
        expect( $r )->toBe( 'DELETE FROM `example`' );
    } );

    it( 'can have a where condition', function() {
        $r = deleteFrom( 'example' )
            ->where( col( 'id' )->equalTo( 1 ) )
            ->endAsString( SQLType::MYSQL );
        expect( $r )->toBe( 'DELETE FROM `example` WHERE `id` = 1' );
    } );

    it( 'can be converted to string', function() {
        $r = deleteFrom( 'example' )
            ->where( col( 'id' )->equalTo( 1 ) )
            ->end();

        expect( (string) $r )->toBe( 'DELETE FROM example WHERE id = 1' );
    } );

} );