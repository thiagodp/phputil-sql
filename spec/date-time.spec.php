<?php
namespace phputil\sql;

describe( 'date and time functions', function() {

    it( 'contains basic functions', function() {
        $sql = select( now(), date(), time() )->toString( SQLType::MYSQL );
        expect( $sql )->toBe( 'SELECT NOW(), CURRENT_DATE, CURRENT_TIME' );
    } );

    describe( 'extract()', function() {

        it( 'can extract a piece of a date for a column', function() {
            $sql = select( extract( Extract::DAY, 'col1' ) )->from( 'example' )->endAsString( SQLType::MYSQL );
            expect( $sql )->toBe( 'SELECT EXTRACT(DAY FROM `col1`) FROM `example`' );
        } );

        it( 'can extract a piece of a date value', function() {
            $sql = select( extract( Extract::DAY, val( '2025-12-31' ) ) )->toString( SQLType::MYSQL );
            expect( $sql )->toBe( "SELECT EXTRACT(DAY FROM '2025-12-31')" );
        } );

        it( 'can receive another date function', function() {
            $sql = select( extract( Extract::DAY, now() ) )->toString( SQLType::MYSQL );
            expect( $sql )->toBe( "SELECT EXTRACT(DAY FROM NOW())" );
        } );

    } );

} );