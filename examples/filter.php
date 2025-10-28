<?php
// ----------------------------------------------------------------------------
// filter.php
//
// This file is an example on how to create queries with optional parameters.
//
// You can execute this file from the CLI or from a Web Server:
//
// a) CLI           : php filter.php
//
// b) Web Server    : php -S localhost:8080
//                    Then open the browser at localhost:8080/filter.php
//
// When executing it from a web server, the following URL parameters are accepted:
//  - "description" for products containing the given description;
//  - "price" for max price; and
//  - "_p" (that means "page") for pagination.
//
//  Example: localhost:8080/filter.php?description=USB&price=100
//
// The CLI version will ask for these parameters. You can leave them blank if
// you prefer.
// ----------------------------------------------------------------------------

require_once __DIR__ . '/../vendor/autoload.php';
use phputil\sql\{SQL};
use function phputil\sql\{select, col, param, orAll, insertInto};

$isCLI = php_sapi_name() === 'cli';

$filterByDescription = htmlspecialchars(
    $isCLI ? readline( 'Description filter: ' ) : ( $_GET[ 'description' ] ?? '' )
);
$filterByPrice = htmlspecialchars(
    $isCLI ? readline( 'Price filter: ' ) : ( $_GET[ 'price' ] ?? '' )
);

$page = htmlspecialchars(
    $isCLI ? readline( 'Page filter: ' ) : ( $_GET[ '_p' ] ?? '' )
);

$conditions = [];
$parameters = [];

if ( ! empty( $filterByDescription ) ) {
    $description = 'description';
    $conditions []= col( $description )->contain( param( $description ) );
    $parameters[ $description ] = '%' . $filterByDescription . '%';
}
if ( ! empty( $filterByPrice ) && is_numeric( $filterByPrice ) ) {
    $price = 'price';
    $conditions []= col( $price )->lessThanOrEqualTo( param( $price ) );
    $parameters[ $price ]= (float) $filterByPrice;
}

$sql = select( 'sku', 'description', 'price' )->from( 'product' );

if ( ! empty( $conditions ) ) {
    $sql = $sql->where( orAll( ...$conditions ) ); // 👈
}

if ( ! empty( $page ) && is_numeric( $page ) && $page > 0 ) {
    $rowsPerPage = 10;
    $offset = ( $page - 1 ) * $rowsPerPage;
    $sql = $sql->limit( $rowsPerPage )->offset( $offset );
}

// Example with PDO and SQLite
SQL::useSQLite();

try {
    $db = __DIR__ . '/example.sqlite';
    $needsStructure = ! @file_exists( $db );

    $pdo = new PDO( "sqlite:$db" );
    if ( $needsStructure ) {
        $pdo->exec( 'CREATE TABLE product (id INTEGER PRIMARY KEY, sku TEXT, `description` TEXT, price NUMERIC);' );
        $pdo->exec( insertInto( 'product' )->values(
            [1,  "100001", "Wireless Mouse",             19.99],
            [2,  "100002", "Bluetooth Keyboard",        29.99],
            [3,  "100003", "USB-C Charger",              15.49],
            [4,  "100004", "Laptop Stand",               43.00],
            [5,  "100005", "16GB USB Drive",             8.75],
            [6,  "100006", "Wireless Earbuds",           59.99],
            [7,  "100007", "External Hard Drive",        79.95],
            [8,  "100008", "HDMI Cable 2m",              9.99],
            [9,  "100009", "Gaming Mouse Pad",           12.49],
            [10, "100010", "Portable Power Bank",        25.00],
            [11, "100011", "Smartphone Case",            14.30],
            [12, "100012", "Webcam 1080p",                39.99],
            [13, "100013", "Wireless Presenter",         22.00],
            [14, "100014", "Noise Cancelling Headphones",85.00],
            [15, "100015", "USB Hub 4 Ports",             18.00],
            [16, "100016", "Laptop Sleeve 15 Inch",       20.00],
            [17, "100017", "LED Desk Lamp",               27.50],
            [18, "100018", "Mechanical Keyboard",         68.99],
            [19, "100019", "Smartwatch",                  150.00],
            [20, "100020", "Tablet Stand",                16.75],
            [21, "100021", "MicroSD 64GB",                22.00],
            [22, "100022", "Ethernet Cable 1m",           7.99],
            [23, "100023", "Wireless Charger Pad",       19.50],
            [24, "100024", "Gaming Headset",              45.00],
            [25, "100025", "Laptop Cooling Pad",          34.00],
        ) );
    }
    $pdoStatement = $pdo->prepare( $sql, [ PDO::FETCH_ASSOC ] );
    $pdoStatement->execute( $parameters );
    echo 'PRODUCTS ', str_repeat( '-', 40 ), PHP_EOL;
    foreach ( $pdoStatement as $p ) {
        echo sprintf( "%6s %-30s $ %7.2f\n", $p[ 'sku' ], $p[ 'description' ], $p[ 'price' ] );
    }
} catch ( PDOException $e ) {
    die( 'Ops... ' . $e->getMessage() );
}