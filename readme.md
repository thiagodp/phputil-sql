# phputil/sql

> 🪄 Simply the best SQL query builder for PHP

⚠️ **Work-In-Progress!**

Features:
- Cross-database support:  MySQL, PostgreSQL, SQLite, Oracle, and SQLServer.
- Fluid, SQL-like syntax.
- Quote characters (like backticks) are included automatically.
- Support to complex queries.
- Include utility functions for: aggregation, string, date, time, and math.


## Install

> Requires PHP 8.0+

```bash
composer require phputil/sql
```


## Basic Usage

ℹ️ Queries must end with the `end()` method or the `endAsString( DBType $dbType = DBType::NONE )` method.

```php
require_once 'vendor/autoload.php';
use phputil\sql\{DB, DBType};
use function phputil\sql\{select, col};

// Simple, database-independent query
echo select()->from( 'example' )->end(); // SELECT * FROM example

// Same query, but converts into a specific database
echo select()->from( 'example' )->endAsString( DBType::MYSQL ); // SELECT * FROM `example`

// Let's change the default database to MYSQL
DB::useMySQL();

// Now queries are converted to MySQL
echo select()->from( 'example' )->end(); // SELECT * FROM `example`

// But you still can choose another database using endAsString()
echo select()->from( 'example' )->endAsString( DBType::SQLSERVER ); // SELECT * FROM [example]

// Let's build a more complete query:
//  All products with price between 100 and 999.999, order by SKU and with a paginated result

$sql = select( 'p.sku', 'p.description', 'p.quantity', 'u.name AS unit', 'p.price' )->
    from( 'product p' )->
    leftJoin( 'unit u' )->on(
        col( 'u.id' )->equalTo( col( 'p.unit_id' ) )
    )->
    where(
        col( 'p.price' )->between( 100.00, 999.99 ) )
    )->
    orderBy( 'p.sku' )->
    limit( 10 )-> // limit to 10 rows
    offset( 20 )-> // skip the first 20 rows (e.g., 3rd page in 10-row pagination)
    end();

echo $sql, PHP_EOL;
// It generates:
//
// SELECT `p`.`sku`, `p`.`description`, `p`.`quantity`, `u`.`name` AS `unit`, `p`.`price`
// FROM `product` `p`
// LEFT JOIN `unit` `u`
//   ON `u`.`id` = `p`.`unit_id`
// WHERE `p`.`price` BETWEEN 100 AND 999.99
// ORDER BY `p`.`sku` ASC
// LIMIT 10
// OFFSET 20

// 👉 You can still convert to another database: 😉
echo $sql->toString( DBType::ORACLE );
// It generates:
//
// SELECT "p"."sku", "p"."description", "p"."quantity", "u"."name" AS "unit", "p"."price"
// FROM "product" "p"
// LEFT JOIN "unit" "u"
//  ON "u"."id" = "p"."unit_id"
// WHERE "p"."price" BETWEEN 100 AND 999.99
// ORDER BY "p"."sku" ASC
// OFFSET 20 ROWS
// FETCH NEXT 10 ROWS ONLY
```

➡️ See more examples in the [API section](#api).

## API

ℹ️ **Note**: Most examples of generated queries are in MySQL.

Index:
- [Types](#types)
    - [`DB`](#db), [`DBType`](#dbtype)
- [Basic functions](#basic-functions)
    - [`select`](#select), [`selectDistinct`](#selectdistinct), [`col`](#col), [`val`](#val), [`param`](#param), [`wrap`](#wrap)
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

#### `DBType`

`DBType` is an enum type with the available database types: `NONE`, `MYSQL`, `POSTGRESQL`, `SQLITE`, `ORACLE`, and `SQLSERVER`.

Example:
```php
use phputil\sql\{DBType};
use function phputil\sql\{select};

echo select()->from( 'example' )->endAsString( DBType::NONE );
```

#### `DB`

`DB` is a class with static attributes that keeps the default database type for queries.

```php
use phputil\sql\{DB};

echo DB::$type; // Get the current database type - by default, it is DBType::NONE

// The following methods will change DB::$type
DB::useNone(); // No specific database - that is, change to DBType::NONE
DB::useMySQL(); // Change to DBType::MYSQL
DB::usePostgreSQL(); // Change to DBType::POSTGRESQL
DB::useSQLite(); // Change to DBType::SQLITE
DB::useOracle(); // Change to DBType::ORACLE
DB::useSQLServer(); // Change to DBType::SQLSERVER
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

$sql = select( 'name', 'email' )->from( 'user' )->where( col( 'id' )->equalTo( 123 ) )->end();
// SELECT `name`, `email` FROM `user` WHERE `id` = 123
```

#### `selectDistinct`

Create a distinct selection. It can receive one or more columns. Examples:

```php
$sql = selectDistinct( 'name' )->from( 'customer' )->where( col( 'name' )->like( 'John%' ) )->end();
// SELECT DISTINCT `name` FROM `customer` WHERE `name` LIKE 'John%'
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
 - `like( $text )` for `LIKE`
 - `startWith( $text )` for `LIKE` with `%` at the beginning of the value
 - `endWith( $text )` for `LIKE` with `%` at the end of the value
 - `contain( $text )` for `LIKE` with `%` around the value
 - `between( $min, $max )` for `BETWEEN` with a minimum and a maximum value
 - `in( $selectionOrArray )` for a sub select statement or an array of values
 - `isNull()` for `IS NULL`
 - `isNotNull()` for `IS NOT NULL`
 - `isTrue()` for `IS TRUE`
 - `isFalse()` for `IS FALSE`

ℹ️ **Notes**:
- Methods `startWith`, `endWith`, and `contain` produce a `LIKE` expression that adds `%` to the receive value.
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

`wrap` add parenthesis around a condition. Example:

```php
$sql = select( 'id' )->from( 'sale' )->where(
    col( 'total' )->greaterThanOrEqualTo( 100 )->
    and( wrap(
        col( 'customer_id' )->equalTo( 1234 )->
        or( col( 'customer_id' )->equalTo( 4567 ) )
    ) )
)->end();
// SELECT `id` FROM `sale` WHERE `total` >= 100 AND (`customer_id` = 1234 OR `customer_id` = 4567)
```


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
$sql = select( sum( 'price * quantity' )->as( 'subtotal' ) )->from( 'sale' )->end();

// Alias as the second argument
$sql = select( sum( 'price * quantity', 'subtotal' ) )->from( 'sale' )->end();
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
Documentation soon

#### `diffInDays`
Documentation soon

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
$sql = select( upper('name') )->from( 'example' )->end();
// MySQL        : SELECT UPPER(`name`) FROM `customer`
// PostgreSQL   : SELECT UPPER("name") FROM "customer"
// SQLite       : SELECT UPPER(`name`) FROM `customer`
// Oracle       : SELECT UPPER("name") FROM "customer"
// SQLServer    : SELECT UPPER([name]) FROM [customer]
```

#### `lower`

`lower( $textOrColumn )` converts a text or column to lowercase. Example:

```php
$sql = select( lower('name') )->from( 'customer' )->end();
// MySQL        : SELECT LOWER(`name`) FROM `customer`
// PostgreSQL   : SELECT LOWER("name") FROM "customer"
// SQLite       : SELECT LOWER(`name`) FROM `customer`
// Oracle       : SELECT LOWER("name") FROM "customer"
// SQLServer    : SELECT LOWER([name]) FROM [customer]
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

`ifNull( $valueOrColumm, $valueOrColumnIfNull )` creates a fallback for a column value when it is null. Example:

```php
$sql = select( 'name', ifNull( 'nickname', val( 'anonymous' ) ) )->from( 'user' )->end();
// SELECT `name`, COALESCE(`nickname`, 'anonymous') FROM `user`

$sql = select( 'name', ifNull( 'nickname', 'name' ) )->from( 'user' )->end();
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
    - [x] Aggregation functions
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
- [ ] Insert statement
- [ ] Update statement
- [ ] Delete statement

👉 Contribute to include another database or feature by opening an [Issue](https://github.com/thiagodp/phputil-sql/issues) or a [Pull Request](://github.com/thiagodp/phputil-sql/pulls).


## License

[MIT](LICENSE) ©️ [Thiago Delgado Pinto](https://github.com/thiagodp)
