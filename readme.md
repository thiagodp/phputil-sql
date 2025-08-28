# phputil/sql

> 🪄 Simply the best SQL query builder for PHP

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


## Examples

ℹ️ **Note**: Queries must end with the `end()` method or the `toString()` method.


```php
require_once 'vendor/autoload.php';
use phputil\sql\{DB};
use function phputil\sql\{select, col};

DB::useMySQL();

$sql = select( 'p.sku', 'p.description', 'p.quantity', 'u.name AS unit', 'p.price' )->
    from( 'product p' )->
    leftJoin( 'unit u' )->on(
        col( 'u.id' )->equalTo( col( 'p.unit_id' ) )
    )->
    where(
        col( 'p.price' )->greaterThan( 100.00 )->and( col( 'p.price' )->lessThan( 999.99 ) )
    )->
    orderBy( 'p.sku' )->
    limit( 10 )-> // limit to 10 rows
    offset( 20 )-> // skip the first 20 rows (e.g., 3rd page in 10-row pagination)
    toString();

// It generates:
//
// SELECT `p`.`sku`, `p`.`description`, `p`.`quantity`, `u`.`name` AS `unit`, `p`.`price`
// FROM `product` `p`
// LEFT JOIN `unit` `u`
//      ON `u`.`id` = `p`.`unit_id`
// WHERE `p`.`price` >= 100 AND `p`.`price` <= 999.99
// ORDER BY `p`.`sku` ASC
// LIMIT 10 OFFSET 20
```

## Supported Databases

- [x] MySQL
- [x] PostgreSQL
- [x] SQLite
- [x] SQL Server
- [x] Oracle

Contribute to include another database or feature by opening an [Issue](https://github.com/thiagodp/phputil-sql/issues) or a [Pull Request](://github.com/thiagodp/phputil-sql/pulls).


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


## API

ℹ️ **Note**: All examples of generated queries are in MySQL.

### Basic functions

```php
// 👉 Make sure to declare their usage. Example:
use function phputil\sql\{select, col, val, wrap};
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

### String functions

#### `upper`

`upper($text)` converts a text or field to uppercase. Example:

```php
$sql = select( upper('foo') )->from( 'example' )->end();
// MySQL        : SELECT UPPER(`foo`) FROM `example`
// PostgreSQL   : SELECT UPPER("foo") FROM "example"
// SQLite       : SELECT UPPER(`foo`) FROM `example`
// Oracle       : SELECT UPPER("foo") FROM "example"
// SQLServer    : SELECT UPPER([foo]) FROM [example]
```

#### `lower`

`lower($text)` converts a text or field to lowercase. Example:

```php
$sql = select( lower('foo') )->from( 'example' )->end();
// MySQL        : SELECT LOWER(`foo`) FROM `example`
// PostgreSQL   : SELECT LOWER("foo") FROM "example"
// SQLite       : SELECT LOWER(`foo`) FROM `example`
// Oracle       : SELECT LOWER("foo") FROM "example"
// SQLServer    : SELECT LOWER([foo]) FROM [example]
```


## License

[MIT](LICENSE) ©️ [Thiago Delgado Pinto](https://github.com/thiagodp)
