<?php
namespace phputil\sql;

use DateTime;

require_once 'vendor/autoload.php';

DB::useMySQL();

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
    end();


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
    toString();

echo $sql;


echo "\n\n\n";

echo "\n", selectDistinct( 'name' )->from( 'customer' )->where( col( 'name' )->like( 'John%' ) )->end();

echo "\n", select( 'total' )->from( 'sale' )->where( val( 123 )->equalTo( col( 'id' ) ) )->end();
echo "\n", select( 'name', 'email' )->from( 'user' )->where( col( 'id' )->equalTo( 123 ) )->end();
echo "\n", select( 'total' )->from( 'sale' )->where( col( 'id' )->equalTo( 123 ) )->end();
echo "\n", select( 'id' )->from( 'product' )->where( col( 'qty' )->lessThan( col( 'min_qty' ) ) )->end();
echo "\n", select( 'name' )->from( 'product' )->where( col( 'special' )->isTrue() )->end();

echo "\n", select( 'name' )->from( 'customer' )->where( col( 'email' )->equalTo( quote( 'bob@acme.com' ) ) )->end();

echo "\n", select( 'name' )->from( 'customer' )->where( col( 'birthdate' )->greaterThanOrEqualTo( quote( '2000-01-01' ) ) )->end();

echo "\n", select( 'id' )->from( 'sale' )->where(
    col( 'total' )->greaterThanOrEqualTo( 100 )->and(
    wrap( col( 'customer_id' )->equalTo( 1234 )->
            or( col( 'customer_id' )->equalTo( 4567 ) ) ) )
)->end();

echo "\n", select( 'id' )->from( 'sale' )->where( col( 'customer_id' )->in( [ 1234, 4567, 7890 ] ) )->end();
echo "\n", select( 'id' )->from( 'sale' )->where( col( 'customer_id' )->in(
    select( 'id' )->from( 'customer' )->where( col( 'salary' )->greaterThan( 100000 ) )
) )->end();

echo "\n", select( '*' )->from( 'foo' )->end();
echo "\n", select()->from( 'foo' )->end();
// echo "\n", select( '*', alias( 'bar', 'b'  ) )->from( 'foo' )->end();
// echo "\n", select( 'x.*', alias( 'y.bar', 'b'  ) )->from( 'x', 'y' )->end();

echo "\n";
echo "\n", select( 'foo' );
echo "\n", select( col('foo' ) );
echo "\n", select( val('foo' ) );
echo "\n", select( val( 1 ) );
echo "\n", select( "first_name + ' ' + last_name AS name" );
echo "\n";

// DB::usePostgreSQL();

echo "\n", select( now() );
echo "\n", select( date() );
echo "\n", select( time() );

echo "\n", select( 'one', 'two' )->from( 'three' )->where( col( 'one' )->equalTo( '2025-01-01' ) )->end();

$now = new \DateTime();
echo "\n", select( 'one', 'two' )->from( 'three' )->where(
    col( 'one' )->equalTo( $now )->
        or( col( 'one' )->greaterThan( $now ) )->
        or( col( 'one' )->between( $now, $now ) )
    )->end();

echo "\n", select( 'one', 'two' )->from( 'three' )->where( col( 'one' )->equalTo( col('one') ) )->end();
echo "\n", select( 'one', 'two' )->from( 'three' )->where( val( $now )->equalTo( col('one') ) )->end();

DB::useOracle();

echo "\n", select( lower('foo') )->from( 'example' )->end();
echo "\n", select( lower(val('Hello')) )->from( 'example' )->end();