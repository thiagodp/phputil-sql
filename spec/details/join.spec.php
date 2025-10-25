<?php
namespace phputil\sql;

describe( 'join', function() {

    describe( 'join', function() {

        it( 'can join a single table', function() {

            $r = select('a.foo', 'b.*')->from( 'a' )
                ->join( 'b' )->on( col( 'b.id' )->equalTo( col( 'a.id' ) ) )
                ->endAsString( SQLType::MYSQL );

            $joinB = 'JOIN `b` ON `b`.`id` = `a`.`id`';
            expect( $r )->toBe( "SELECT `a`.`foo`, `b`.* FROM `a` $joinB" );
        } );

        it( 'can join more than one table', function() {

            $r = select('a.foo', 'b.*', 'c.*')->from( 'a' )
                ->join( 'b' )->on( col( 'b.id' )->equalTo( col( 'a.id' ) ) )
                ->join( 'c' )->on( col( 'c.id' )->equalTo( col( 'a.id' ) ) )
                ->join( 'd' )->on( col( 'd.id' )->equalTo( col( 'a.id' ) ) )
                ->endAsString( SQLType::MYSQL );

            $joinB = 'JOIN `b` ON `b`.`id` = `a`.`id`';
            $joinC = 'JOIN `c` ON `c`.`id` = `a`.`id`';
            $joinD = 'JOIN `d` ON `d`.`id` = `a`.`id`';
            expect( $r )->toBe( "SELECT `a`.`foo`, `b`.*, `c`.* FROM `a` $joinB $joinC $joinD" );
        } );

    } );


    describe( 'inner join', function() {

        it( 'can join a single table', function() {

            $r = select('a.foo', 'b.*')->from( 'a' )
                ->innerJoin( 'b' )->on( col( 'b.id' )->equalTo( col( 'a.id' ) ) )
                ->endAsString( SQLType::MYSQL );

            $joinB = 'INNER JOIN `b` ON `b`.`id` = `a`.`id`';
            expect( $r )->toBe( "SELECT `a`.`foo`, `b`.* FROM `a` $joinB" );
        } );

        it( 'can join more than one table', function() {

            $r = select('a.foo', 'b.*', 'c.*')->from( 'a' )
                ->innerJoin( 'b' )->on( col( 'b.id' )->equalTo( col( 'a.id' ) ) )
                ->innerJoin( 'c' )->on( col( 'c.id' )->equalTo( col( 'a.id' ) ) )
                ->innerJoin( 'd' )->on( col( 'd.id' )->equalTo( col( 'a.id' ) ) )
                ->endAsString( SQLType::MYSQL );

            $joinB = 'INNER JOIN `b` ON `b`.`id` = `a`.`id`';
            $joinC = 'INNER JOIN `c` ON `c`.`id` = `a`.`id`';
            $joinD = 'INNER JOIN `d` ON `d`.`id` = `a`.`id`';
            expect( $r )->toBe( "SELECT `a`.`foo`, `b`.*, `c`.* FROM `a` $joinB $joinC $joinD" );
        } );

    } );


    describe( 'left join', function() {

        it( 'can join a single table', function() {

            $r = select('a.foo', 'b.*')->from( 'a' )
                ->leftJoin( 'b' )->on( col( 'b.id' )->equalTo( col( 'a.id' ) ) )
                ->endAsString( SQLType::MYSQL );

            $joinB = 'LEFT JOIN `b` ON `b`.`id` = `a`.`id`';
            expect( $r )->toBe( "SELECT `a`.`foo`, `b`.* FROM `a` $joinB" );
        } );

        it( 'can join more than one table', function() {

            $r = select('a.foo', 'b.*', 'c.*')->from( 'a' )
                ->leftJoin( 'b' )->on( col( 'b.id' )->equalTo( col( 'a.id' ) ) )
                ->leftJoin( 'c' )->on( col( 'c.id' )->equalTo( col( 'a.id' ) ) )
                ->leftJoin( 'd' )->on( col( 'd.id' )->equalTo( col( 'a.id' ) ) )
                ->endAsString( SQLType::MYSQL );

            $joinB = 'LEFT JOIN `b` ON `b`.`id` = `a`.`id`';
            $joinC = 'LEFT JOIN `c` ON `c`.`id` = `a`.`id`';
            $joinD = 'LEFT JOIN `d` ON `d`.`id` = `a`.`id`';
            expect( $r )->toBe( "SELECT `a`.`foo`, `b`.*, `c`.* FROM `a` $joinB $joinC $joinD" );
        } );

    } );


    describe( 'right join', function() {

        it( 'can join a single table', function() {

            $r = select('a.foo', 'b.*')->from( 'a' )
                ->rightJoin( 'b' )->on( col( 'b.id' )->equalTo( col( 'a.id' ) ) )
                ->endAsString( SQLType::MYSQL );

            $joinB = 'RIGHT JOIN `b` ON `b`.`id` = `a`.`id`';
            expect( $r )->toBe( "SELECT `a`.`foo`, `b`.* FROM `a` $joinB" );
        } );

        it( 'can join more than one table', function() {

            $r = select('a.foo', 'b.*', 'c.*')->from( 'a' )
                ->rightJoin( 'b' )->on( col( 'b.id' )->equalTo( col( 'a.id' ) ) )
                ->rightJoin( 'c' )->on( col( 'c.id' )->equalTo( col( 'a.id' ) ) )
                ->rightJoin( 'd' )->on( col( 'd.id' )->equalTo( col( 'a.id' ) ) )
                ->endAsString( SQLType::MYSQL );

            $joinB = 'RIGHT JOIN `b` ON `b`.`id` = `a`.`id`';
            $joinC = 'RIGHT JOIN `c` ON `c`.`id` = `a`.`id`';
            $joinD = 'RIGHT JOIN `d` ON `d`.`id` = `a`.`id`';
            expect( $r )->toBe( "SELECT `a`.`foo`, `b`.*, `c`.* FROM `a` $joinB $joinC $joinD" );
        } );

    } );


    describe( 'full join', function() {

        it( 'can join a single table', function() {

            $r = select('a.foo', 'b.*')->from( 'a' )
                ->fullJoin( 'b' )->on( col( 'b.id' )->equalTo( col( 'a.id' ) ) )
                ->endAsString( SQLType::SQLSERVER );

            $joinB = 'FULL JOIN [b] ON [b].[id] = [a].[id]';
            expect( $r )->toBe( "SELECT [a].[foo], [b].* FROM [a] $joinB" );
        } );

        it( 'can join more than one table', function() {

            $r = select('a.foo', 'b.*', 'c.*')->from( 'a' )
                ->fullJoin( 'b' )->on( col( 'b.id' )->equalTo( col( 'a.id' ) ) )
                ->fullJoin( 'c' )->on( col( 'c.id' )->equalTo( col( 'a.id' ) ) )
                ->fullJoin( 'd' )->on( col( 'd.id' )->equalTo( col( 'a.id' ) ) )
                ->endAsString( SQLType::SQLSERVER );

            $joinB = 'FULL JOIN [b] ON [b].[id] = [a].[id]';
            $joinC = 'FULL JOIN [c] ON [c].[id] = [a].[id]';
            $joinD = 'FULL JOIN [d] ON [d].[id] = [a].[id]';
            expect( $r )->toBe( "SELECT [a].[foo], [b].*, [c].* FROM [a] $joinB $joinC $joinD" );
        } );

    } );


    describe( 'cross join', function() {

        it( 'can join a single table', function() {

            $r = select('a.foo', 'b.*')->from( 'a' )
                ->crossJoin( 'b' )
                ->endAsString( SQLType::MYSQL );

            $joinB = 'CROSS JOIN `b`';
            expect( $r )->toBe( "SELECT `a`.`foo`, `b`.* FROM `a` $joinB" );
        } );

        it( 'can join more than one table', function() {

            $r = select('a.foo', 'b.*', 'c.*')->from( 'a' )
                ->crossJoin( 'b' )
                ->crossJoin( 'c' )
                ->crossJoin( 'd' )
                ->endAsString( SQLType::MYSQL );

            $joinB = 'CROSS JOIN `b`';
            $joinC = 'CROSS JOIN `c`';
            $joinD = 'CROSS JOIN `d`';
            expect( $r )->toBe( "SELECT `a`.`foo`, `b`.*, `c`.* FROM `a` $joinB $joinC $joinD" );
        } );

    } );


    describe( 'natural join', function() {

        it( 'can join a single table', function() {

            $r = select('a.foo', 'b.*')->from( 'a' )
                ->naturalJoin( 'b' )
                ->endAsString( SQLType::MYSQL );

            $joinB = 'NATURAL JOIN `b`';
            expect( $r )->toBe( "SELECT `a`.`foo`, `b`.* FROM `a` $joinB" );
        } );

        it( 'can join more than one table', function() {

            $r = select('a.foo', 'b.*', 'c.*')->from( 'a' )
                ->naturalJoin( 'b' )
                ->naturalJoin( 'c' )
                ->naturalJoin( 'd' )
                ->endAsString( SQLType::MYSQL );

            $joinB = 'NATURAL JOIN `b`';
            $joinC = 'NATURAL JOIN `c`';
            $joinD = 'NATURAL JOIN `d`';
            expect( $r )->toBe( "SELECT `a`.`foo`, `b`.*, `c`.* FROM `a` $joinB $joinC $joinD" );
        } );

    } );


} );