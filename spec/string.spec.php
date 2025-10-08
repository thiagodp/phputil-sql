<?php
namespace phputil\sql;

describe( 'string functions', function() {

    it( 'has now()', function() {
        $r = now()->toString( SQLType::MYSQL );
        expect( $r )->toBe( 'NOW()' );
    } );

    it( 'has date()', function() {
        $r = date()->toString( SQLType::MYSQL );
        expect( $r )->toBe( 'CURRENT_DATE' );
    } );

    it( 'has time()', function() {
        $r = time()->toString( SQLType::MYSQL );
        expect( $r )->toBe( 'CURRENT_TIME' );
    } );

    describe( 'extract()', function() {

        it( 'can be called with a unit and a column', function() {
            $r = extract( Extract::DAY, 'birth' )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'EXTRACT(DAY FROM `birth`)' );
        } );

        it( 'can be called with a unit and a date value', function() {
            $r = extract( Extract::DAY, val( '2025/01/31' ) )->toString( SQLType::MYSQL );
            expect( $r )->toBe( "EXTRACT(DAY FROM '2025/01/31')" );
        } );

        it( 'can be called with a unit a declare "from" later', function() {
            $r = extract( Extract::DAY )->from( val( '2025/01/31' ) )->toString( SQLType::MYSQL );
            expect( $r )->toBe( "EXTRACT(DAY FROM '2025/01/31')" );
        } );

        it( 'when called without "from" it receive an empty value', function() {
            $r = extract( Extract::DAY )->toString( SQLType::MYSQL );
            expect( $r )->toBe( "EXTRACT(DAY FROM '')" );
        } );

    } );

} );