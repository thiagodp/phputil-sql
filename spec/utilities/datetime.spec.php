<?php
namespace phputil\sql;

describe( 'date and time functions', function() {

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
            $r = extract( Extract::DAY )->from( val( '2025-01-31' ) )->toString( SQLType::MYSQL );
            expect( $r )->toBe( "EXTRACT(DAY FROM '2025-01-31')" );
        } );

        it( 'when called without "from" it receive an empty value', function() {
            $r = extract( Extract::DAY )->toString( SQLType::MYSQL );
            expect( $r )->toBe( "EXTRACT(DAY FROM '')" );
        } );

        it( 'can receive another date function', function() {
            $r = extract( Extract::DAY, now() )->toString( SQLType::MYSQL );
            expect( $r )->toBe( 'EXTRACT(DAY FROM NOW())' );
        } );

    } );

    describe( 'addDays', function() {

        it( 'can receive another date function as value', function() {
            $sql = select( addDays( 'field', diffInDays( val('2024-12-31'), now() ) ) )->toString( SQLType::MYSQL );
            expect( $sql )->toBe( "SELECT DATE_ADD(`field`, INTERVAL DATEDIFF('2024-12-31', NOW()) DAY)" );
        } );
    } );


    describe( 'subDays', function() {

        it( 'can receive another date function as value', function() {
            $sql = select( subDays( 'field', diffInDays( val('2024-12-31'), now() ) ) )->toString( SQLType::MYSQL );
            expect( $sql )->toBe( "SELECT DATE_SUB(`field`, INTERVAL DATEDIFF('2024-12-31', NOW()) DAY)" );
        } );
    } );


    describe( 'dateAdd', function() {

        it( 'can receive another date function as value', function() {
            $sql = select( dateAdd( 'field', diffInDays( val('2024-12-31'), now() ) ) )->toString( SQLType::MYSQL );
            expect( $sql )->toBe( "SELECT DATE_ADD(`field`, INTERVAL DATEDIFF('2024-12-31', NOW()) DAY)" );
        } );
    } );


    describe( 'dateSub', function() {

        it( 'can receive another date function as value', function() {
            $sql = select( dateSub( 'field', diffInDays( val('2024-12-31'), now() ) ) )->toString( SQLType::MYSQL );
            expect( $sql )->toBe( "SELECT DATE_SUB(`field`, INTERVAL DATEDIFF('2024-12-31', NOW()) DAY)" );
        } );
    } );

} );