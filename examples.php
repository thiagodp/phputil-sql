<?php
namespace phputil\sql;

use DateTime;

require_once 'vendor/autoload.php';

// DB::useMySQL();

$dbType = DBType::MYSQL;

echo str_repeat( '-', 70 ), "\n";
echo "\n", select( 'a AS hello', 'b', 'o.c', '1 as hi', countDistinct( 'dice', 'd' ) )->
    from( 'table t', 'other o' )->
    leftJoin( 'foo f' )->
        on( col( 'f.id' )->equalTo( col('t.f_id') ) )->
    where(
        col( 'a' )->equalTo( 10 )->
        and( wrap( col( 'b' )->greaterThan( 20 )->or( val( 1 )->equalTo( 1 ) ) )->
        and( val( 0 )->equalTo( 0 ) ) )->
        or( col( 'a' )->in( select( 'foo' )->from( 'bar' ) ) )->
        or( col( 'b' )->isNull() )->
        or( col( 'b' )->isFalse() )->
        or( col( 'c') ->equalTo( 'Hello' ) )->
        or( col('a' )->in( [ 10, 20, '30' ] ) )
    )->
    groupBy( 'a', 'b', 'c' )->
        having( col( 'b' )->greaterThan( 25 ) )->
    orderBy( 'b', desc( 'c' ) )->
    limit( 10 )->
    offset( 20 )->
    unionDistinct(
        select( 'x', 'y', 'z' )->from( 'zoo' )->end()
    )->
    end()->toString( $dbType );


// echo PHP_EOL, __parseColumnAndAlias( 'foo' );

echo "\n\n";



$sql = select( 'p.sku', 'p.description', 'p.quantity', 'u.name AS unit', 'p.price' )->
    from( 'product p' )->
    leftJoin( 'unit u' )->on(
        col( 'u.id' )->equalTo( col( 'p.unit_id' ) )
    )->
    where(
        col( 'p.price' )->greaterThanOrEqualTo( 100.00 )->and( col( 'p.price' )->lessThanOrEqualTo( 999.99 ) )
    )->
    orderBy( 'p.sku' )->
    limit( 10 )-> // limit to 10 rows
    offset( 20 )-> // skip the first 20 rows (e.g., 3rd page in 10-row pagination)
    endAsString( $dbType );

echo $sql;


echo "\n\n\n";

echo "\n", selectDistinct( 'name' )->from( 'customer' )->where( col( 'name' )->like( 'John%' ) )->endAsString( $dbType );

echo "\n", select( 'total' )->from( 'sale' )->where( val( 123 )->equalTo( col( 'id' ) ) )->endAsString( $dbType );
echo "\n", select( 'name', 'email' )->from( 'user' )->where( col( 'id' )->equalTo( 123 ) )->endAsString( $dbType );
echo "\n", select( 'total' )->from( 'sale' )->where( col( 'id' )->equalTo( 123 ) )->endAsString( $dbType );
echo "\n", select( 'id' )->from( 'product' )->where( col( 'qty' )->lessThan( col( 'min_qty' ) ) )->endAsString( $dbType );
echo "\n", select( 'name' )->from( 'product' )->where( col( 'special' )->isTrue() )->endAsString( $dbType );

echo "\n", select( 'name' )->from( 'customer' )->where( col( 'email' )->equalTo( quote( 'bob@acme.com' ) ) )->endAsString( $dbType );

echo "\n", select( 'name' )->from( 'customer' )->where( col( 'birthdate' )->greaterThanOrEqualTo( quote( '2000-01-01' ) ) )->endAsString( $dbType );

echo "\n", select( 'id' )->from( 'sale' )->where(
    col( 'total' )->greaterThanOrEqualTo( 100 )->and(
    wrap( col( 'customer_id' )->equalTo( 1234 )->
            or( col( 'customer_id' )->equalTo( 4567 ) ) ) )
    )->endAsString( $dbType );

echo "\n", select( 'id' )->from( 'sale' )->where( col( 'customer_id' )->in( [ 1234, 4567, 7890 ] ) )->endAsString( $dbType );
echo "\n", select( 'id' )->from( 'sale' )->where( col( 'customer_id' )->in(
    select( 'id' )->from( 'customer' )->where( col( 'salary' )->greaterThan( 100000 ) )
) )->endAsString( $dbType );

echo "\n", select( '*' )->from( 'foo' )->endAsString( $dbType );
echo "\n", select()->from( 'foo' )->endAsString( $dbType );
// echo "\n", select( '*', alias( 'bar', 'b'  ) )->from( 'foo' )->endAsString( $dbType );
// echo "\n", select( 'x.*', alias( 'y.bar', 'b'  ) )->from( 'x', 'y' )->endAsString( $dbType );

echo "\n--------------------------";
echo "\n", select( 'foo' )->toString( $dbType );
echo "\n", select( col( 'foo' ) )->toString( $dbType );
echo "\n", select( val( 'foo' ) )->toString( $dbType );
echo "\n", select( val( 1 ) )->toString( $dbType );
echo "\n", select( "first_name + ' ' + last_name AS name" )->toString( $dbType );
echo "\n";

// DB::usePostgreSQL();

echo "\n", select( now() )->toString( $dbType );
echo "\n", select( now()->as( 'n' ) )->toString( $dbType );
echo "\n", select( date() )->toString( $dbType );
echo "\n", select( time() )->toString( $dbType );

echo "\n", select( 'one', 'two' )->from( 'three' )->where( col( 'one' )->equalTo( '2025-01-01' ) )->endAsString( $dbType );

$now = new \DateTime();
echo "\n", select( 'one', 'two' )->from( 'three' )->where(
    col( 'one' )->equalTo( $now )->
        or( col( 'one' )->greaterThan( $now ) )->
        or( col( 'one' )->between( $now, $now ) )
    )->endAsString( $dbType );

echo "\n", select( 'one', 'two' )->from( 'three' )->where( col( 'one' )->equalTo( col('one') ) )->endAsString( $dbType );
echo "\n", select( 'one', 'two' )->from( 'three' )->where( val( $now )->equalTo( col('one') ) )->endAsString( $dbType );

DB::useMySQL();

echo "\n", select( lower('foo') )->from( 'example' )->endAsString( $dbType );
echo "\n", select( lower(val('Hello')) )->from( 'example' )->endAsString( $dbType );

echo "\n", select( 'one' )->from( 'example' )->where( col( 'id' )->equalTo( param() ) )->endAsString();
echo "\n", select( 'one' )->from( 'example' )->where( col( 'id' )->equalTo( param('x') ) )->endAsString();

echo "\n MAKES A STRING WITH DEFAULT DB type";
echo "\n", select( 'one' )->from( 'example' )->where( col( 'id' )->equalTo( param('x') ) )->end();

// -----------------------------------------------------------
// FROM THE DOCS
// -----------------------------------------------------------

echo "\n", str_repeat( '-', 50 ), PHP_EOL;

// Simple, database-independent query
echo select()->from( 'example' )->end(), PHP_EOL; // SELECT * FROM example

// Same query, but converts into a specific database
echo select()->from( 'example' )->endAsString( DBType::MYSQL ), PHP_EOL; // SELECT * FROM `example`

// Let's change the default database to MYSQL
DB::useMySQL();

// Now the queries are converted to MySQL
echo select()->from( 'example' )->end(), PHP_EOL; // SELECT * FROM `example`

// But you still can choose using endAsString()
echo select()->from( 'example' )->endAsString( DBType::SQLSERVER ), PHP_EOL; // SELECT * FROM [example]

$sql = select( 'p.sku', 'p.description', 'p.quantity', 'u.name AS unit', 'p.price' )->
    from( 'product p' )->
    leftJoin( 'unit u' )->on(
        col( 'u.id' )->equalTo( col( 'p.unit_id' ) )
    )->
    where(
        col( 'p.price' )->between( 100, 999.99 )
    )->
    orderBy( 'p.sku' )->
    limit( 10 )-> // limit to 10 rows
    offset( 20 )-> // skip the first 20 rows (e.g., 3rd page in 10-row pagination)
    end();

echo $sql, PHP_EOL;

// 👉 You can still convert to another database: 😉
echo $sql->toString( DBType::ORACLE );

// -------------------
// TEMP
// -------------------

echo "\n\n\n\n";

echo $sql = select()->from( 'example' )->orderBy( 'a', asc( 'b' ) )->end(), PHP_EOL;
echo $sql = select()->from( 'example' )->orderBy( 'a', desc( 'b' ) )->end(), PHP_EOL;
// SELECT * FROM `example` ORDER BY `a` ASC, `b` DESC

echo "\n", $sql = select( 'name', ifNull( 'nickname', val( 'anonymous' ) ) )->from( 'user' )->end();

echo "\n", select()->from( 'example' )->end();
echo "\n", select()->from( 'example' )->endAsString( DBType::NONE );