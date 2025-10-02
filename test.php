<?php
namespace phputil\sql;

require_once 'vendor/autoload.php';

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
echo "\n\n\n\n";

echo $sql = select()->from( 'example' )->orderBy( 'a', asc( 'b' ) )->end(), PHP_EOL;
echo $sql = select()->from( 'example' )->orderBy( 'a', desc( 'b' ) )->end(), PHP_EOL;
// SELECT * FROM `example` ORDER BY `a` ASC, `b` DESC

echo "\n", $sql = select( 'name', ifNull( 'nickname', val( 'anonymous' ) ) )->from( 'user' )->end();

echo "\n", select()->from( 'example' )->end();
echo "\n", select()->from( 'example' )->endAsString( DBType::NONE );