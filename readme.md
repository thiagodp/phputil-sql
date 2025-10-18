# phputil/sql

> 🪄 Probably the best SQL query builder for PHP

⚠️ **Work-In-Progress!** ⚠️

Features:
- 🎯 **Cross-database SQL with the same API**: MySQL, PostgreSQL, SQLite, Oracle, and SQLServer.
- 🚀 No database or external dependencies - not even [PDO](https://www.php.net/manual/en/book.pdo.php).
- 🔥 Fluid, typed, SQL-like syntax.
- 🛟 Automatically quote columns and table names (e.g. backticks in MySQL).
- 🪢 Support to complex queries.
- 🛠️ Include utility functions for [aggregation](#aggregate-functions), [string](#string-functions), [date and time](#date-and-time-functions), and [math](#math-functions).

See the [Roadmap](#roadmap)


## Use cases

This library is particularly useful for:
- Creating queries that can be used with different relational databases without the need of (bloated) ORM frameworks.
  - Migration is usually achieved by changing a single line of code in your codebase!
- Writing readable, typo-free SQL statements.
- Building complex WHERE clauses (e.g. for filtering content) without the need of concatenating strings.
- Migrating data from different databases.


## Install

> Requires PHP 8.0+

```bash
composer require phputil/sql
```


## Basic Usage

### Queries

1️⃣ Use the function `select()` for creating a query. Then use the method `endAsString( SQLType $sqlType = SQLType::NONE ): string` for obtaining the SQL for a certain type.


```php
require_once 'vendor/autoload.php';
use phputil\sql\{SQLType};
use function phputil\sql\{select};

echo select()->from( 'example' )->endAsString();
// SELECT * FROM example

echo select( 'colum1', 'column2' )->from( 'example' )->endAsString();
// SELECT column1, column2 FROM example

echo select( 'colum1', 'column2' )->from( 'example' )->endAsString( SQLType::MYSQL );
// SELECT `column1`, `column2` FROM `example`

echo select( 'colum1', 'column2' )->from( 'example' )->endAsString( SQLType::SQLSERVER );
// SELECT [column1], [column2] FROM [example]
```

2️⃣ By using the method `end()`, instead of `endAsString`, the desired database/SQL type is obtained from the static attribute `SQL::$type`:

```php
require_once 'vendor/autoload.php';
use phputil\sql\{SQL, SQLType};
use function phputil\sql\{select};

// No specific SQL is set yet, so SQL::$type is SQLType::NONE

echo select( 'colum1', 'column2' )->from( 'example' )->end();
// SELECT column1, column2 FROM example

// Let's set it to MySQL (SQLType::MYSQL)
SQL::useMySQL();

// Now the same query as above will be converted to MySQL
echo select( 'colum1', 'column2' )->from( 'example' )->end();
// SELECT `column1`, `column2` FROM `example`

SQL::useSQLServer();

echo select( 'colum1', 'column2' )->from( 'example' )->end();
// SELECT [column1], [column2] FROM [example]
```

🆒 Okay, let's build a more complex query.

```php
require_once 'vendor/autoload.php';
use phputil\sql\{SQL, SQLType};
use function phputil\sql\{select, col};

SQL::useMySQL();

// Say, all products with price between 100 and 999.999, quantity above 0,
// ordered by SKU and with a paginated result

$sql = select( 'p.sku', 'p.description', 'p.quantity', 'u.name AS unit', 'p.price' )
    ->from( 'product p' )
    ->leftJoin( 'unit u' )
        ->on( col( 'u.id' )->equalTo( col( 'p.unit_id' ) ) )
    ->where(
        col( 'p.price' )->between( 100.00, 999.99 )
        ->and( col( 'p.quantity' )->greaterThan( 0 ) )
    )
    ->orderBy( 'p.sku' )
    ->limit( 10 ) // limit to 10 rows
    ->offset( 20 ) // skip the first 20 rows (e.g., 3rd page in 10-row pagination)
    ->end();

echo $sql, PHP_EOL;

// It generates:
//
// SELECT `p`.`sku`, `p`.`description`, `p`.`quantity`, `u`.`name` AS `unit`, `p`.`price`
// FROM `product` `p`
// LEFT JOIN `unit` `u`
//   ON `u`.`id` = `p`.`unit_id`
// WHERE `p`.`price` BETWEEN 100 AND 999.99 AND `p`.`quantity` > 0
// ORDER BY `p`.`sku` ASC
// LIMIT 10
// OFFSET 20


// 👉 Since $sql holds an object,
// you can still convert it to another database/SQL type using toString()
echo $sql->toString( SQLType::ORACLE );

// Now it generates:
//
// SELECT "p"."sku", "p"."description", "p"."quantity", "u"."name" AS "unit", "p"."price"
// FROM "product" "p"
// LEFT JOIN "unit" "u"
//  ON "u"."id" = "p"."unit_id"
// WHERE "p"."price" BETWEEN 100 AND 999.99 AND "p"."quantity" > 0
// ORDER BY "p"."sku" ASC
// OFFSET 20 ROWS
// FETCH NEXT 10 ROWS ONLY
```

🤔 Right, but what about SQL Injection?

🆗 Just use parameters - with [`param()`](#param) - for any input values.

👉 Your database must be able to handle parameters in SQL commands. Example with PDO:

```php
// Getting an optional filter from the URL: /products?sku=123456
$sku = htmlspecialchars( $_GET[ 'sku' ] ?? '' );

// Example with named parameters using PDO
$sql = select( 'sku', 'description', 'price' )->from( 'product' );

if ( ! empty( $sku ) ) {
    $sql = $sql->where(
        col( 'sku' )->equal( param( 'sku' ) ) // 👈 Named parameter
    );
}

$pdo = new PDO( 'sqlite:example.db' );
$pdoStatement = $pdo->prepare( $sql->end() );
$pdoStatement->execute( [ 'sku' => $sku ] ); // 👈 Value only here
// ...
```

➡️ See more examples in the [API section](#api).


## Data manipulation

ℹ️ Use `deleteFrom()` for creating a `DELETE` command. Example:

```php
$command = deleteFrom( 'user' )->where( col( 'id' )->equal( 123 ) )->end();
// DELETE FROM `user` WHERE `id` = 123
```

ℹ️ Use `insertInto()` for creating an `INSERT` command. Examples:

```php
// Insert with values only
$command = insertInto( 'user' )->values(
    [ 1, 'Alice Foe', 'alice', 'aL1C3_passW0rD' ],
    [ 2, 'Bob Doe', 'bob', 'just_b0b' ],
)->end();
// INSERT INTO `user`
// VALUES
// (1, 'Alice Foe', 'alice', 'aL1C3_passW0rD'),
// (2, 'Bob Doe', 'bob', 'just_b0b')


// Insert with field names
$command = insertInto( 'user', [ 'name', 'username', 'password' ] )->values(
    [ 'Jack Boo', 'jack', 'b00_jaCK' ],
    [ 'Suzan Noo', 'suzan', 'suuuz4N' ],
)->end();
// INSERT INTO `user` (`name`, `username`, `password`)
// VALUES
// ('Jack Boo', 'jack', 'b00_jaCK'),
// ('Suzan Noo', 'suzan', 'suuuz4N')


// Insert from select
$command = insertInto( 'user', [ 'name', 'username', 'password' ],
    select( 'name', 'nickname', 'ssn' )->from( 'customer' )->end()
)->end();
// INSERT INTO `user` (`name`, `username`, `password`)
// SELECT `name`, `nickname`, `ssn` FROM `customer`
```

ℹ️ Use `update()` for creating an `UPDATE` command. Examples:

```php
$command = update( 'example' )
    ->set( [ 'a' => 10, 'b' => 'b + 1', 'c' => 'c + c * 50/100', 'd' => "'Hello'", 'e' => val( 'World' ) ] )
    ->where( col( 'id' )->equalTo( 1 ) )
    ->endAsString( SQLType::MYSQL );

// UPDATE `example`
// SET `a` = 10, `b` = `b` + 1, `c` = `c` + `c` * 50/100, `d` = 'Hello', `e` = 'World'
// WHERE `id` = 1
```

## API

⚠️ **Note**: Most examples of generated queries are in MySQL. ⚠️

Index:
- [Types](#types)
    - [`SQL`](#sql), [`SQLType`](#sqltype)
- [Basic functions](#basic-functions)
    - [`select`](#select), [`selectDistinct`](#selectdistinct)
    - [`insertInto`](#insertinto), [`update`](#update), [`deleteFrom`](#deletefrom)
    - [`col`](#col), [`val`](#val), [`param`](#param), [`wrap`](#wrap), [`not`](#not)
- [Logic utilities](#logic-utilities)
    - [`andAll`](#andall), [`orAll`](#orall)
- [Ordering utilities](#ordering-utilities)
    - [`asc`](#asc), [`desc`](#desc)
- [Date and time functions](#date-and-time-functions)
    - [`now`](#now), [`date`](#date), [`time`](#time), [`extract`](#extract), [`diffInDays`](#diffindays), [`addDays`](#adddays), [`subDays`](#subdays), [`dateAdd`](#dateadd), [`dateSub`](#datesub)
- [String functions](#string-functions)
    - [`upper`](#upper), [`lower`](#lower), [`substring`](#substring), [`concat`](#concat), [`length`](#length), [`bytes`](#bytes)
- [Null handling function](#null-handling-function)
    - [`ifNull`](#ifnull)
- [Math functions](#math-functions)
    - [`abs`](#abs), [`round`](#round), [`ceil`](#ceil), [`floor`](#floor), [`power`](#power), [`sqrt`](#sqrt), [`sin`](#sin), [`cos`](#cos), [`tan`](#tan)


### Types

#### `SQLType`

`SQLType` is an enum type with these values: `NONE`, `MYSQL`, `POSTGRESQL`, `SQLITE`, `ORACLE`, and `SQLSERVER`.

Example:
```php
use phputil\sql\{SQLType};
use function phputil\sql\{select};

echo select()->from( 'example' )->endAsString( SQLType::NONE );
// SELECT * FROM example
```

#### `SQL`

`SQL` is a class with static attributes that keeps the default SQL type for queries.

```php
use phputil\sql\{SQL};

echo SQL::$type; // Get the current database type - by default, it is SQLType::NONE

// The following methods change SQL::$type
SQL::useNone(); // No specific SQL type - that is, change to SQLType::NONE
SQL::useMySQL(); // Change to SQLType::MYSQL
SQL::usePostgreSQL(); // Change to SQLType::POSTGRESQL
SQL::useSQLite(); // Change to SQLType::SQLITE
SQL::useOracle(); // Change to SQLType::ORACLE
SQL::useSQLServer(); // Change to SQLType::SQLSERVER
```

### Basic functions

```php
// 👉 Make sure to declare their usage. Example:
use function phputil\sql\{select, col, val, param, wrap};
```

#### `select`

Create a selection. Examples:

```php
$sql = select()->from( 'user' )->end();
// SELECT * FROM `user`

$sql = select( 'name', 'email' )
    ->from( 'user' )
    ->where( col( 'id' )->equalTo( 123 ) )
    ->end();
// SELECT `name`, `email` FROM `user` WHERE `id` = 123
```

👉 `from()` returns a `From` object with the following methods:

- `innerJoin( string $table ): Join`
- `leftJoin( string $table ): Join`
- `rightJoin( string $table ): Join`
- `fullJoin( string $table ): Join`
- `crossJoin( string $table ): Join`
- `naturalJoin( string $table ): Join`
- `where( Condition $condition ): From`
- `whereExists( Select $select ): From`
- `groupBy( string ...$columns ): From`
- `having( Condition $condition ): From`
- `orderBy( string ...$columns ): From`
- `union( Select $select ): From`
- `unionDistinct( Select $select ): From`

Example with `having`:

```php
echo select( count( 'id' ), 'country' )
    ->from( 'customer' )
    ->groupBy( 'country' )
    ->having( val( count( 'id' ) )->greaterThan( 5 ) )
    ->orderBy( desc( count( 'id' ) ) )
    ->endAsString( SQLType::MYSQL );

// SELECT COUNT(`id`), `country`
// FROM `customer`
// GROUP BY `country`
// HAVING COUNT(`id`) > 5
// ORDER BY COUNT(`id`) DESC
```


#### `selectDistinct`

Create a distinct selection. It can receive one or more columns. Examples:

```php
$sql = selectDistinct( 'name' )
    ->from( 'customer' )
    ->where( col( 'name' )->like( 'John%' ) )
    ->end();
// SELECT DISTINCT `name` FROM `customer` WHERE `name` LIKE 'John%'
```

#### `insertInto`

`insertInto( string $table, string[] $fields = [], ?Select $select = null )` creates an `INSERT` command.

```php
// With no fields declared, but they are: id, name, email
$command = insertInto( 'contact' )
    ->values(
        [ 1, 'John Doe', 'john@doe.com' ],
        [ 2, 'Suzan Foe', 'suzan@foe.com' ],
    )->end();
// INSERT INTO `contact`
// VALUES
// (1, 'John Doe', 'john@doe.com'),
// (2, 'Suzan Foe', 'suzan@foe.com')


// With fields declared, considering an auto-incremental id
$command = insertInto( 'contact', [ 'name', 'email' ] )
    ->values(
        [ 'John Doe', 'john@doe.com' ],
        [ 'Suzan Foe', 'suzan@foe.com' ],
    )->end();
// INSERT INTO `contact` (`name`, `email`)
// VALUES
// ('John Doe', 'john@doe.com'),
// ('Suzan Foe', 'suzan@foe.com')


// With anonymous parameters
$command = insertInto( 'contact', [ 'name', 'email' ] )
    ->values(
        [ param(), param() ]
    )->end();
// INSERT INTO `contact` (`name`, `email`) VALUES (?, ?)


// With named parameters
$command = insertInto( 'contact', [ 'name', 'email' ] )
    ->values(
        [ param( 'name' ), param( 'email' ) ]
    )->end();
// INSERT INTO `contact` (`name`, `email`) VALUES (:name, :email)


// From selection
$command = insertInto( 'contact', [ 'name', 'email' ],
        select( 'name', 'email' )->from( 'customer' )
            ->where( col( 'email' )->endWith( '@acme.com' ) )
            ->end()
    )->end();
// INSERT INTO `contact` (`name`, `email`)
// SELECT `name`, `email` FROM `customer`
// WHERE `email` LIKE '%@acme.com'
```

#### `update`

`update` creates an `UPDATE` command. Example:

```php
$command = update( 'user' )
    ->set(
        [ 'password' => val( '123456' ), 'last_update' => now() ]
    )->where(
        col( 'id' )->equalTo( 123 )
    )->end();
// UPDATE `user`
// SET `password` = '123456', `last_update` = NOW()
// WHERE `id` = 123
```

#### `deleteFrom`

`deleteFrom` creates a `DELETE` command. Example:

```php
// With anonymous parameter
$command = deleteFrom( 'user' )
    ->where( col( 'id' )->equalTo( param() ) )
    ->end();
// DELETE FROM `user` WHERE `id` = ?


// With named parameter
$command = deleteFrom( 'user' )
    ->where( col( 'id' )->equalTo( param( 'id' ) ) )
    ->end();
// DELETE FROM `user` WHERE `id` = :id
```

#### `col`

`col` makes a column comparison and makes sure that the column is quoted appropriately. Examples:

```php
$sql = select( 'total' )->from( 'sale' )->where( col( 'id' )->equalTo( 123 ) )->end();
// SELECT `total` FROM `sale` WHERE `id` = 123

$sql = select( 'id' )->from( 'product' )->where( col( 'qty' )->lessThan( col( 'min_qty' ) ) )->end();
// SELECT `id` FROM `product` WHERE `qty` < `min_qty`

$sql = select( 'name' )->from( 'product' )->where( col( 'special' )->isTrue() )->end();
// SELECT `name` FROM `product` WHERE `special` IS TRUE

$sql = select( 'id' )->from( 'sale' )->where( col( 'customer_id' )->in( [ 1234, 4567, 7890 ] ) )->end();
// SELECT `id` FROM `sale` WHERE `customer_id` IN (1234, 4567, 7890)

// Sub-select
$sql = select( 'id' )->from( 'sale' )->where( col( 'customer_id' )->in(
    select( 'id' )->from( 'customer' )->where( col( 'salary' )->greaterThan( 100_000 ) )
) )->end();
// SELECT `id` FROM `sale` WHERE `customer_id` IN (SELECT `id` FROM `customer` WHERE `salary` > 100000)
```

`col` returns the following comparison methods:
 - `equalTo( $x )` for `=`
 - `notEqualTo( $x )` or `differentFrom( $x )` for `<>`
 - `lessThan( $x )` for `<`
 - `lessThanOrEqualTo( $x )` for `<=`
 - `greaterThan( $x )` for `>`
 - `greaterThanOrEqualTo( $x )` for `>=`
 - `like( $value )` for `LIKE`
 - `startWith( $value )` for `LIKE` with `%` at the beginning of the value
 - `endWith( $value )` for `LIKE` with `%` at the end of the value
 - `contain( $value )` for `LIKE` with `%` around the value
 - `between( $min, $max )` for `BETWEEN` with a minimum and a maximum value
 - `in( $selectionOrArray )` for a sub select statement or an array of values
 - `isNull()` for `IS NULL`
 - `isNotNull()` for `IS NOT NULL`
 - `isTrue()` for `IS TRUE`
 - `isFalse()` for `IS FALSE`

ℹ️ **Notes**:
- Methods `startWith`, `endWith`, and `contain` produce a `LIKE` expression that adds `%` to the receive value. However, when an anonymous (`?`) or a named (`:name`) parameter is received by them, **they will not add `%`**, and you must add `%` manually to the parameter values.
- In Oracle databases, the methods `isTrue()` and `isFalse()` are supported from Oracle version `23ai`. In older versions, you can use `equalTo(1)` and `equalTo(0)` respectively, for the same results.

👉 `col` can also be used for creating aliases, with the `as` method. For instance, these three examples are equivalent:

```php
$sql = select( col( 'long_name' )->as( 'l' ) );
$sql = select( col( 'long_name AS l' ) );
$sql = select( 'long_name AS l' );
```

#### `val`

`val( $value )` allows a value to be in the left side of a comparison. Example:

```php
$sql = select( 'total' )->from( 'sale' )->where( val( 123 )->equalTo( col( 'id' ) ) )->end();
// SELECT `total` FROM `sale` WHERE 123 = `id`
```
ℹ️ **Note**: `val` returns the same comparison operators as [`col`](#col).


`val` can also be used in a select statement for defining values or functions. Example:
```php
$sql = select( val( 1 ) );
// SELECT 1
```

#### `param`

`param` establishes an anonymous or named parameter. Examples:

```php
// Calling param() without an argument makes an anonymous parameter
$sql = select( 'total' )->from( 'sale' )->where( col( 'id' )->equalTo( param() ) )->end();
// SELECT `total` FROM `sale` WHERE `id` = ?

// Calling param() with an argument makes a named parameter
$sql = select( 'total' )->from( 'sale' )->where( col( 'id' )->equalTo( param( 'id' ) ) )->end();
// SELECT `total` FROM `sale` WHERE `id` = :id
```

#### `wrap`

`wrap` adds parenthesis around a condition. Example:

```php
$sql = select( 'id' )->from( 'sale' )
    ->where(
        col( 'total' )->greaterThanOrEqualTo( 100 )
        ->and( wrap(
            col( 'customer_id' )->equalTo( 1234 )
            ->or( col( 'customer_id' )->equalTo( 4567 ) )
        ) )
    )->end();
// SELECT `id` FROM `sale`
// WHERE `total` >= 100 AND (`customer_id` = 1234 OR `customer_id` = 4567)
```

#### `not`

`not` negates a condition. Example:

```php
$sql = select( 'name' )->from( 'customer' )
    ->where(
        not( col( 'name' )->like( '% % %' ) )
    )->end();
// SELECT `name` FROM `customer`
// WHERE NOT(`name` LIKE '% % %')
```

### Logic utilities

These are especially useful for creating a WHERE condition that unites a bunch of other conditions with the same logic operator.

#### `andAll`

`andAll()` concatenates all the received conditions with the AND operator. Example:

```php
$condition = andAll(
    col( 'description' )->startWith( 'Mouse' ),
    col( 'price' )->lessThanOrEqualTo( 300.00 )
);

$sql = select()->from( 'product' )->where( $condition )->end();
// SELECT * FROM `product`
// WHERE `description` LIKE 'Mouse%' AND `price` <= 300
```

ℹ️ _Tip_: You can use the spread operator (`...`) for passing an array of conditions to `andAll()`. Just make sure that your array is not empty, before doing that.

#### `orAll`

`orAll()` concatenates all the received conditions with the OR operator. Example:

```php
$condition = orAll(
    col( 'description' )->startWith( 'Mouse' ),
    col( 'sku' )->contain( 'MZ' )
);

$sql = select()->from( 'product' )->where( $condition )->end();
// SELECT * FROM `product`
// WHERE `description` LIKE 'Mouse%' OR `sku` LIKE '%MZ%'
```

ℹ️ _Tip_: You can use the spread operator (`...`) for passing an array of conditions to `orAll()`. Just make sure that your array is not empty, before doing that.

### Ordering utilities

#### `asc`

`asc()` indicates an ascending sort order. Its usage is **optional**. Example:

```php
$sql = select()->from( 'example' )->orderBy( 'a', asc( 'b' ) )->end();
// SELECT * FROM `example` ORDER BY `a` ASC, `b` ASC
```

#### `desc`

`desc()` makes an descending sort. Example:

```php
$sql = select()->from( 'example' )->orderBy( 'a', desc( 'b' ) )->end();
// SELECT * FROM `example` ORDER BY `a` ASC, `b` DESC
```

### Aggregate functions

Aggregate functions can receive an alias as a second argument or use the method `as` to define an alias. For instance, these two commands are equivalent:

```php
// Alias using the method as()
$sql = select(
        'date',
        sum( 'price * quantity' )->as( 'subtotal' ), // 👈
    )->from( 'sale' )
    ->groupBy( 'date' )
    ->end();

// Alias as the second argument
$sql = select(
        'date',
        sum( 'price * quantity', 'subtotal' ), // 👈
    )->from( 'sale' )
    ->groupBy( 'date' )
    ->end();
```

#### `count`

```php
$sql = select( count( 'id' ) )->from( 'sale' )->end();
```

#### `countDistinct`

```php
$sql = select( countDistinct( 'phone_number' ) )->from( 'contact' )->end();
```

#### `sum`

```php
$sql = select( sum( 'total' ) )->from( 'order' )->end();
```

#### `sumDistinct`

```php
$sql = select( sumDistinct( 'commission' ) )->from( 'sale' )->end();
```

#### `avg`

```php
$sql = select( avg( 'price' ) )->from( 'product' )->end();
```

#### `avgDistinct`

```php
$sql = select( avgDistinct( 'receive_qty' ) )->from( 'purchase' )->end();
```

#### `min`

```php
$sql = select( min( 'price' ) )->from( 'product' )->end();
```

#### `max`

```php
$sql = select( max( 'price' ) )->from( 'product' )->end();
```


### Date and Time functions

#### `now`

`now()` returns the current date and time, in most databases. Example:

```php
$sql = select( now() );
// MySQL        : SELECT NOW()
// PostgreSQL   : SELECT NOW()
// SQLite       : SELECT DATETIME('now')
// Oracle       : SELECT SYSDATE
// SQLServer    : SELECT CURRENT_TIMESTAMP
```

#### `date`

`date()` returns the current date. Example:

```php
$sql = select( date() );
// MySQL        : SELECT CURRENT_DATE
// PostgreSQL   : SELECT CURRENT_DATE
// SQLite       : SELECT CURRENT_DATE
// Oracle       : SELECT SYSDATE
// SQLServer    : SELECT GETDATE()
```

#### `time`

`time()` returns the current time, in most databases. Example:

```php
$sql = select( time() );
// MySQL        : SELECT CURRENT_TIME
// PostgreSQL   : SELECT CURRENT_TIME
// SQLite       : SELECT CURRENT_TIME
// Oracle       : SELECT CURRENT_TIMESTAMP
// SQLServer    : SELECT CURRENT_TIMESTAMP
```

#### `extract`

`extract()` can extract a piece of a column or a date/time/timestamp value. Examples:

```php
use phputil\sql\{SQLType, Extract};
use function phputil\sql\{select, extract};

$sql = select( extract( Extract::DAY, 'col1' ) )
    ->from( 'example' )->endAsString( SQLType::MYSQL );
// SELECT EXTRACT(DAY FROM `col1`) FROM `example`

$sql = select( extract( Extract::DAY, val( '2025-12-31' ) ) )
    ->toString( SQLType::MYSQL );
// SELECT EXTRACT(DAY FROM '2025-12-31')
```

This is the `Extract` enum:

```php
enum Extract {
    case YEAR;
    case MONTH;
    case DAY;

    case HOUR;
    case MINUTE;
    case SECOND;
    case MICROSECOND;

    case QUARTER;
    case WEEK;
    case WEEK_DAY;
}
```

#### `diffInDays`

`diffInDays` returns the difference in days from two dates/timestamps.

```php
echo select( diffInDays( val( '31-12-2024' ), now() ) )
    ->toString( SQLType:MYSQL );
// SELECT DATEDIFF('31-12-2024', NOW())

echo select( diffInDays( 'birthdate', now() ) )->from( 'example' )
    ->toString( SQLType:MYSQL );
// SELECT DATEDIFF(`birthdate`, NOW()) FROM `example`
```

#### `addDays`
Documentation soon

#### `subDays`
Documentation soon

#### `dateAdd`
Documentation soon

#### `dateSub`
Documentation soon


### String functions

#### `upper`

`upper( $textOrColumn )` converts a text or column to uppercase. Example:

```php
$sql = select( upper( 'name' ) )->from( 'customer' )->end();
//  SELECT UPPER(`name`) FROM `customer`
```

#### `lower`

`lower( $textOrColumn )` converts a text or column to lowercase. Example:

```php
$sql = select( lower( 'name' ) )->from( 'customer' )->end();
// SELECT LOWER(`name`) FROM `customer`
```

#### `substring`
Documentation soon

#### `concat`
Documentation soon

#### `length`
Documentation soon

#### `bytes`
Documentation soon

### Null handling function

#### `ifNull`

`ifNull( $valueOrColumm, $valueOrColumnIfNull )` creates a fallback value for a column when it is null. Examples:

```php
$sql = select( 'name', ifNull( 'nickname', val( 'anonymous' ) ) )
    ->from( 'user' )->end();
// SELECT `name`, COALESCE(`nickname`, 'anonymous') FROM `user`

$sql = select( 'name', ifNull( 'nickname', 'name' ) )
    ->from( 'user' )->end();
// SELECT `name`, COALESCE(`nickname`, `name`) FROM `user`
```

### Math functions

#### `abs`
Documentation soon

#### `round`
Documentation soon

#### `ceil`
Documentation soon

#### `floor`
Documentation soon

#### `power`
Documentation soon

#### `sqrt`
Documentation soon

#### `sin`
Documentation soon

#### `cos`
Documentation soon

#### `tan`
Documentation soon

## Roadmap

- [x] Select statement
    - [x] Complex where clauses
    - [x] Joins
    - [x] Sub-queries
    - [x] Limit and Offset
    - [x] Aggregate functions
    - [x] Distinct for selections and aggregation functions
    - [x] Null handling function
    - [x] Common date and time functions
    - [x] Common string functions
    - [x] Common mathematical functions
    - [x] Automatic value conversions:
        - [x] Add apostrophes to string values.
        - [x] DateTime values as database strings.
        - [x] Boolean and NULL values.
        - [x] Array values inside `in` expressions.
    - [x] Aggregate functions in order by clauses
    - [x] Aggregate functions in having clauses - by using [val()](#val)
    - [ ] Simulate certain JOIN clauses
- [ ] Options for SQL generation
    - [ ] Add argument for avoiding escaping names
- [x] Delete statement
    - [x] WHERE clause
- [x] Insert statement
    - [x] with SELECT clause
- [ ] Update statement

👉 Contribute by opening an [Issue](https://github.com/thiagodp/phputil-sql/issues) or making a [Pull Request](https://github.com/thiagodp/phputil-sql/pulls).


## License

[MIT](LICENSE) ©️ [Thiago Delgado Pinto](https://github.com/thiagodp)
