<?php
namespace phputil\sql;

use \DateTimeInterface;
use \Stringable; // PHP 8.0+

// ----------------------------------------------------------------------------

interface Condition extends Stringable {

    public function and( Condition $other ): Condition;
    public function andNot( Condition $other ): Condition;
    public function or( Condition $other ): Condition;
    public function orNot( Condition $other ): Condition;
}

// ----------------------------------------------------------------------------

abstract class BasicCondition implements Condition { // Composite

    public function and( Condition $other ): Condition {
        return new AndCondition( $this, $other );
    }

    public function andNot( Condition $other ): Condition {
        return new AndNotCondition( $this, $other );
    }

    public function or( Condition $other ): Condition {
        return new OrCondition( $this, $other );
    }

    public function orNot( Condition $other ): Condition {
        return new OrNotCondition( $this, $other );
    }

}

// ----------------------------------------------------------------------------
// Conditional Operators
// ----------------------------------------------------------------------------

abstract class BinaryCondition extends BasicCondition {
    public function __construct(
        protected Condition $left,
        protected Condition $right
    ) {
    }
}

// ----------------------------------------------------------------------------

class AndCondition extends BinaryCondition {

    public function __toString(): string {
        return $this->left . ' AND ' . $this->right;
    }
}

// ----------------------------------------------------------------------------

class AndNotCondition extends BinaryCondition {

    public function __toString(): string {
        return $this->left . ' AND NOT ' . $this->right;
    }
}

// ----------------------------------------------------------------------------

class OrCondition extends BinaryCondition {

    public function __toString(): string {
        return $this->left . ' OR ' . $this->right;
    }
}

// ----------------------------------------------------------------------------

class OrNotCondition extends BinaryCondition {

    public function __toString(): string {
        return $this->left . ' OR NOT ' . $this->right;
    }
}

// ----------------------------------------------------------------------------
// BETWEEN
// ----------------------------------------------------------------------------

class BetweenCondition extends BasicCondition {

    protected $leftSide;
    protected $rightSide;

    /**
     * @param string $columnName
     * @param string|int|float|bool|Stringable|From $leftSide
     * @param string|int|float|bool|Stringable|From $rightSide
     */
    public function __construct(
        protected string $columnName,
        $leftSide,
        $rightSide
    ) {
        if ( $leftSide instanceof From || $leftSide instanceof Stringable ) {
            $this->leftSide = $leftSide;
        } else {
            $this->leftSide = __toValue( $leftSide );
        }

        if ( $rightSide instanceof From || $rightSide instanceof Stringable ) {
            $this->rightSide = $rightSide;
        } else {
            $this->rightSide = __toValue( $rightSide );
        }
    }

    public function __toString(): string {
        return $this->columnName . ' BETWEEN ' . $this->leftSide . ' AND ' . $this->rightSide;
    }
}

// ----------------------------------------------------------------------------
// Comparison Operators
// ----------------------------------------------------------------------------

abstract class ComparisonOperator extends BasicCondition {

    protected $leftSide;
    protected $rightSide;

    /**
     * @param string|int|float|bool|Stringable|From $leftSide
     * @param string|int|float|bool|Stringable|From $rightSide
     */
    public function __construct(
        $leftSide,
        $rightSide
    ) {
        // if ( $leftSide instanceof From || $leftSide instanceof Stringable ) {
        //     $this->leftSide = $leftSide;
        // } else {
        //     $this->leftSide = __addApostropheIfNeeded( $leftSide );
        // }

        if ( is_bool( $leftSide ) ) {
            $this->leftSide = __toBoolean( $leftSide );
        } else if ( $leftSide instanceof DateTimeInterface ) {
            $this->leftSide = __toDateString( $leftSide );
        } else {
            $this->leftSide = $leftSide;
        }

        if ( $rightSide instanceof From || $rightSide instanceof Stringable ) {
            $this->rightSide = $rightSide;
        } else {
            $this->rightSide = __toValue( $rightSide );
        }

        // if ( is_string( $rightSide ) ) {
        //     $rightSide = __addApostropheIfNeeded( $rightSide );
        // } else if ( is_bool( $rightSide ) ) {
        //     $rightSide = __booleanString( $rightSide );
        // } else if ( $rightSide instanceof DateTimeInterface ) {
        //     $rightSide = __toDatabaseDate( $rightSide );
        // }
        // $this->rightSide = $rightSide;
    }

}

class EqualCondition extends ComparisonOperator {

    public function __toString(): string {
        return $this->leftSide . ' = ' . $this->rightSide;
    }
}

class NotEqualCondition extends ComparisonOperator {

    public function __toString(): string {
        return $this->leftSide . ' <> ' . $this->rightSide;
    }
}

class GreaterThanCondition extends ComparisonOperator {

    public function __toString(): string {
        return $this->leftSide . ' > ' . $this->rightSide;
    }
}

class GreaterThanOrEqualToCondition extends ComparisonOperator {

    public function __toString(): string {
        return $this->leftSide . ' >= ' . $this->rightSide;
    }
}

class LessThanCondition extends ComparisonOperator {

    public function __toString(): string {
        return $this->leftSide . ' < ' . $this->rightSide;
    }
}

class LessThanOrEqualToCondition extends ComparisonOperator {

    public function __toString(): string {
        return $this->leftSide . ' <= ' . $this->rightSide;
    }
}

class LikeCondition extends ComparisonOperator {

    public function __toString(): string {
        return $this->leftSide . ' LIKE ' . $this->rightSide;
    }
}

class InCondition extends ComparisonOperator {

    public function __toString(): string {
        $right = $this->rightSide;
        if ( $this->rightSide instanceof From ) {
            $right = $this->rightSide->end();
        } else if ( is_array( $this->rightSide ) ) {
            $right = array_map( fn( $x ) => __toValue( $x ), $this->rightSide );
            $right = implode( ', ', $right );
        }
        return $this->leftSide . ' IN (' . $right . ')';
    }
}

class IsCondition extends ComparisonOperator {

    public function __toString(): string {
        return $this->leftSide . ' IS ' . $this->rightSide;
    }
}

// ----------------------------------------------------------------------------

abstract class ComparableContent implements Stringable {

    /**
     * @var Condition[] $conditions
     */
    protected $conditions = [];

    public function __construct(
        protected $content
    ) {
    }

    protected function add( Condition $c ): Condition {
        $this->conditions []= $c;
        return $c;
    }

    public function equalTo( $rightSide ): Condition {
        return $this->add(
            new EqualCondition( $this->content, $rightSide )
        );
    }

    public function notEqualTo( $rightSide ): Condition {
        return $this->add(
            new NotEqualCondition( $this->content, $rightSide )
        );
    }

    /** Alias for `notEqualTo()` */
    public function differentFrom( $rightSide ): Condition {
        return $this->notEqualTo( $rightSide );
    }

    public function greaterThan( $rightSide ): Condition {
        return $this->add(
            new GreaterThanCondition( $this->content, $rightSide )
        );
    }

    public function greaterThanOrEqualTo( $rightSide ): Condition {
        return $this->add(
            new GreaterThanOrEqualToCondition( $this->content, $rightSide )
        );
    }

    public function lessThan( $rightSide ): Condition {
        return $this->add(
            new LessThanCondition( $this->content, $rightSide )
        );
    }

    public function lessThanOrEqualTo( $rightSide ): Condition {
        return $this->add(
            new LessThanOrEqualToCondition( $this->content, $rightSide )
        );
    }

    public function like( $rightSide ): Condition {
        return $this->add(
            new LikeCondition( $this->content, $rightSide )
        );
    }

    public function startWith( $rightSide ): Condition {
        return $this->like( $rightSide . '%' );
    }

    public function endWith( $rightSide ): Condition {
        return $this->like( '%' . $rightSide );
    }

    public function contain( $rightSide ): Condition {
        return $this->like( '%' . $rightSide . '%' );
    }

    public function between( $min, $max ): Condition {
        return $this->add(
            new BetweenCondition( $this->content, $min, $max )
        );
    }

    /**
     * The value must be included in a query or array of values.
     * @param \phputil\sql\From|string[]|int[]|float[] $selection
     * @return Condition
     */
    public function in( From|array $selection ): Condition {
        return $this->add(
            new InCondition( $this->content, $selection )
        );
    }

    public function isNull(): Condition {
        return $this->add(
            new IsCondition( $this->content, new Value( 'NULL' ) )
        );
    }

    public function isNotNull(): Condition {
        return $this->add(
            new IsCondition( $this->content, new Value( 'NOT NULL' ) )
        );
    }

    public function isTrue(): Condition {
        return $this->add(
            new IsCondition( $this->content, new Value( 'TRUE' ) )
        );
    }

    public function isFalse(): Condition {
        return $this->add(
            new IsCondition( $this->content, new Value( 'FALSE' ) )
        );
    }


    public function __toString(): string {
        if ( empty( $this->conditions ) ) {
            return $this->content;
        }
        return $this->content . ' ' . __conditionsToString( $this->conditions );
    }

    public function __content() {
        return $this->content;
    }
}

class Column extends ComparableContent {
}

class Value extends ComparableContent {
}

//=====

enum DBType: string {
    case NONE = 'none';
    case MYSQL = 'mysql';
    case SQLITE = 'sqlite';
    case ORACLE = 'oracle';
    case POSTGRESQL = 'postgresql';
    case SQLSERVER = 'sqlserver';
}

class DB {
    public static DBType $type = DBType::NONE;

    public static function useNone(): void { self::$type = DBType::NONE; }
    public static function useMySQL(): void { self::$type = DBType::MYSQL; }
    public static function useSQLite(): void { self::$type = DBType::SQLITE; }
    public static function usePostgreSQL(): void { self::$type = DBType::POSTGRESQL; }
    public static function useSQLServer(): void { self::$type = DBType::SQLSERVER; }
    public static function useOracle(): void { self::$type = DBType::ORACLE; }
}


class Select implements Stringable {

    protected bool $distinct;
    protected array $columns;
    protected ?From $from = null;

    /**
     * Constructor
     *
     * @param bool $distinct
     * @param string[] $columns
     */
    public function __construct( bool $distinct, ...$columns ) {
        $this->distinct = $distinct;
        if ( empty( $columns ) ) {
            $columns = [ '*' ];
        }
        $this->columns = array_map( fn($c) => __parseColumnAndAlias( $c ), $columns );
    }

    /**
     * Selects from one or more tables.
     *
     * @param string $table
     * @param string[] $tables
     * @return From
     */
    public function from( string $table, ...$tables ): From {
        array_unshift( $tables, $table );
        $tableData = [];
        foreach ( $tables as $t ) {
            $pieces = __parseSeparatedValues( $t );
            $tableName = __asName( $pieces[ 0 ] ?? '' );
            $tableAlias = __asName( $pieces[ 1 ] ?? '' );
            $tableData []= new TableData( $tableName, $tableAlias );
        }
        $this->from = new From( $this, $tableData );
        return $this->from;
    }

    public function __toString(): string {
        if ( empty( $this->columns ) ) {
            return '';
        }
        $from = $this->from ? $this->from : '';
        return 'SELECT ' . ( $this->distinct ? 'DISTINCT ' : '' ) .
            implode( ', ', $this->columns ) . $from;
    }
}


class TableData implements Stringable {

    public function __construct(
        public readonly string $tableName,
        public readonly string $tableAlias,
    ) {
    }


    public function __toString(): string {
        if ( $this->tableAlias != '' ) { // It has an alias
            return $this->tableName . ' ' . $this->tableAlias;
        }
        return $this->tableName;
    }
}


class From {

    use CanLimit;

    /** @var Condition[] $whereConditions */
    protected array $whereConditions = [];

    /** @var Join[] $joins */
    protected array $joins = [];

    /** @var string[] $groupByColumns */
    protected array $groupByColumns = [];

    protected ?Condition $havingCondition = null;

    /** @var ColumnOrdering[] $columnOrderings */
    protected array $columnOrderings = [];
    protected ?Select $unionSelect = null;
    protected bool $isUnionDistinct = false;

    /**
     * Constructor
     *
     * @param \phputil\sql\Select $parent
     * @param string[] $tables
     */
    public function __construct(
        protected Select $parent,
        protected array $tables
    ) {
    }

    protected function makeJoin( string $table, string $type ): Join {
        $values = __parseSeparatedValues( $table );
        $declaration = __asName( $values[ 0 ] );
        if ( isset( $values[ 1 ] ) ) {
            $declaration .= ' ' . __asName( $values[ 1 ] );
        }
        $j = new Join( $this, $declaration, $type );
        $this->joins []= $j;
        return $j;
    }

    public function innerJoin( string $table ): Join {
        return $this->makeJoin( $table, 'INNER JOIN' );
    }

    public function leftJoin( string $table ): Join {
        return $this->makeJoin( $table, 'LEFT JOIN' );
    }

    public function rightJoin( string $table ): Join {
        return $this->makeJoin( $table, 'RIGHT JOIN' );
    }

    public function fullJoin( string $table ): Join {
        return $this->makeJoin( $table, 'FULL JOIN' );
    }

    public function crossJoin( string $table ): Join {
        return $this->makeJoin( $table, 'CROSS JOIN' );
    }

    public function naturalJoin( string $table ): Join {
        return $this->makeJoin( $table, 'NATURAL JOIN' );
    }


    /**
     * @param Condition[] $conditions
     */
    public function where( ...$conditions ): self {
        $this->whereConditions = $conditions;
        return $this;
    }


    /**
     * @param string[] $columns
     */
    public function groupBy( ...$columns ): self {
        $columns = array_map( fn($c) => __parseColumnAndAlias( $c ), $columns );
        $this->groupByColumns = $columns;
        return $this;
    }

    public function having( Condition $condition ): self {
        $this->havingCondition = $condition;
        return $this;
    }

    /**
     * @param string[] $columnNames
     */
    public function orderBy( ...$columnNames ): self {
        $this->columnOrderings = array_map( fn($c) => new ColumnOrdering( $c ), $columnNames );
        return $this;
    }

    public function union( Select $select ): self {
        $this->isUnionDistinct = false;
        $this->unionSelect = $select;
        return $this;
    }

    public function unionDistinct( Select $select ): self {
        $this->isUnionDistinct = true;
        $this->unionSelect = $select;
        return $this;
    }

    public function end(): Select {
        return $this->parent;
    }

    public function toString(): string {
        return $this->end()->__toString();
    }

    public function __toString(): string {
        $s = ' FROM ' . implode( ', ', $this->tables );

        foreach ( $this->joins as $j ) {
            $s .= ' ' . $j;
        }

        $where = __conditionsToString( $this->whereConditions );
        if ( $where != '' ) {
            $s .= ' WHERE' . $where;
        }

        if ( ! empty( $this->groupByColumns ) ) {
            $s .= ' GROUP BY ' . implode( ', ', $this->groupByColumns );

            if ( $this->havingCondition ) {
                $s .= ' HAVING ' . $this->havingCondition;
            }
        }

        if ( ! empty( $this->columnOrderings )) {
            $s .= ' ORDER BY ' . implode( ', ', $this->columnOrderings );
        }

        $limitOffset = $this->makeLimitAndOffset();
        if ( ! empty( $limitOffset ) ) {
            $s .= $limitOffset;
        }

        if ( $this->unionSelect !== null ) {
            $s .= ' UNION ' . ( $this->isUnionDistinct ? 'DISTINCT ' : '' ) . $this->unionSelect;
        }

        return $s;
    }
}


class Join implements Stringable {

    protected Condition $condition;

    public function __construct(
        protected From $parent,
        protected string $table,
        protected string $type,
    ) {
    }

    public function on( Condition $condition ): From {
        $this->condition = $condition;
        return $this->parent;
    }

    public function __toString(): string {
        return $this->type . ' ' . $this->table . ' ON ' . $this->condition;
    }
}


class ColumnOrdering implements Stringable {

    protected string $column;
    protected string $direction;

    public function __construct(
        string $column
    ) {
        $pieces = __parseSeparatedValues( $column );
        $this->column = __parseColumnAndAlias( $pieces[ 0 ] ?? '' );
        $this->direction = strtoupper( $pieces[ 1 ] ?? 'ASC' ) == 'DESC' ? 'DESC' : 'ASC';
    }

    public function __toString(): string {
        return $this->column . ' ' . $this->direction;
    }
}


trait CanLimit {

    protected int $limitValue = -1;
    protected int $offsetValue = -1;

    public function limit(int $value): self {
        if ( $value > 0 ) {
            $this->limitValue = $value;
        }
        return $this;
    }

    public function offset(int $value): self {
        if ( $value > 0 ) {
            $this->offsetValue = $value;
        }
        return $this;
    }

    /**
     * Compatible with: MySQL, PostgreSQL, SQLite, Amazon Aurora, SAP HANA.
     *
     * @return string
     */
    protected function makeLimitAndOffset(): string {

        // Compatible with: SQLServer, Oracle.

        if ( array_search( DB::$type, [ DBType::ORACLE, DBType::SQLSERVER ], true ) ) {
            $s = '';
            if ( $this->offsetValue > 0 ) {
                $s .= ' OFFSET ' . $this->offsetValue . ' ROWS';
            }
            if ( $this->limitValue > 0 ) {
                $s .= ' FETCH NEXT ' . $this->limitValue . ' ROWS ONLY';
            }
            return $s;
        }

        // Compatible with: MySQL, PostgreSQL, SQLite, Amazon Aurora, SAP HANA.

        $s = '';
        if ( $this->limitValue > 0 ) {
            $s .= ' LIMIT ' . $this->limitValue;
        }
        if ( $this->offsetValue > 0 ) {
            $s .= ' OFFSET ' . $this->offsetValue;
        }
        return $s;
    }

}


class Wrap extends BasicCondition {

    public function __construct(
        protected Condition $condition
    ) {
    }

    public function __toString(): string {
        return '('. $this->condition . ')';
    }
}

// ----------------------------------------------------------------------------
// INTERNAL
// ----------------------------------------------------------------------------

function __getQuoteCharacters( DBType $dbType ): array {
    return match( $dbType ) {
        DBType::NONE => [ '', '' ], // Empty
        DBType::MYSQL, DBType::SQLITE => [ '`', '`' ], // Backticks
        DBType::ORACLE, DBType::POSTGRESQL => [ '"', '"' ], // Quotes
        DBType::SQLSERVER => [ '[', ']' ], // Square brackets
    };
}

function __parseSeparatedValues( string $column ): array {
    $pieces = explode( ' ', $column );
    $pieces = array_map( 'trim', $pieces );
    return array_filter( $pieces, fn($v) => $v != '' );
}


/**
 * Convert conditions to string.
 *
 * @param Condition[] $conditions
 */
function __conditionsToString( array $conditions ): string {
    $h = '';
    foreach ( $conditions as $c ) {
        $h .= ' ' . $c;
    }
    return $h;
}

function __parseColumnAndAlias( int|float|bool|string|ComparableContent $column ): string {

    // if ( $column instanceof Value ) {
    //     return (string) $column;
    // }

    if ( ! is_string( $column ) ) {
        return (string) $column;
    }

    $regex = '/^[ ]*([^ ]+)[ ]*(?: AS )?[ ]*([^ ]+)?$/i';
    $matches = [];
    if ( preg_match( $regex, $column, $matches ) ) {
        $column = $matches[ 1 ];
        if ( ! is_numeric( $column ) ) {
            $pieces = explode( '.', $column );
            if ( isset( $pieces[ 1 ] ) ) {
                $table = __asName( $pieces[ 0 ] );
                $column = $table . '.' . __asName( $pieces[ 1 ] );
            } else {
                $column = __asName( $pieces[ 0 ] );
            }
        }
        if ( isset( $matches[ 2 ] ) ) {
            $alias = __asName( trim( $matches[ 2 ] ) );
            return $column . ' AS ' . $alias;
        }
    }
    return $column;
}

function __asName( string $name ): string {
    if ( $name === '*' ) { // Ignore quotes for a star
        return $name;
    }
    $quotes = __getQuoteCharacters( DB::$type );
    if ( $quotes[ 0 ] != '' && $name != '' && $name[ 0 ] != $quotes[ 0 ] ) {
        return $quotes[ 0 ] . $name . $quotes[ 1 ];
    }
    return $name;
}

function __makeFunction( $function ): Value {
    return new Value( $function );
}

function __toString( string $value ): string { // Do not use it directly. Use __toValue() instead.
    $value = trim( $value );
    if ( empty( $value ) ) {
        return "''";
    }
    if ( ( $value[ 0 ] ?? "'" ) != "'" ) {
        return "'" . $value . "'";
    }
    return $value;
}


function __toDateString( DateTimeInterface $value ): string {
    $r = $value->format( 'Y-m-d' );
    return "'$r'";
}

function __toBoolean( bool $value, bool $asInteger = false ): string {
    if ( $asInteger ) {
        return $value ? '1' : '0';
    }
    return $value ? 'TRUE' : 'FALSE';
}

function __toValue( $value, bool $isOracleDatabase = false ) {

    if ( is_null( $value ) ) {
        return 'NULL';
    } else if ( is_string( $value ) ) {
        return __toString( $value );
    } else if ( $value instanceof Value ) {
        $content = $value->__content();
        if ( is_string( $content ) || is_numeric( $content ) ) {
            return (string) $content;
        }
        return __toValue( $content );
    } else if ( $value instanceof Column ) {
        return __asName( (string) $value );
    } else if ( $value instanceof DateTimeInterface ) {
        return __toDateString( $value );
    } else if ( is_bool( $value ) ) {
        return __toBoolean( $value, $isOracleDatabase || DB::$type === DBType::ORACLE );
    } else if ( is_array( $value ) ) {
        return $value;
    }

    return "$value"; // to string
}

function __valueOrName( $str ): string {
    if ( is_string( $str ) ) {
        $str = __asName( $str );
    } else if ( $str instanceof Column ) {
        $str = __asName( $str );
    } else if ( $str instanceof Value ) {
        $str = __toValue( $str->__content() );
    }
    return $str; // to string
}

function __makeAggregateFunction( string $function, bool $distinct, $textOrColumn, string $alias = '' ): string {
    $textOrColumn = __valueOrName( $textOrColumn );
    $alias = __asName( $alias );
    $dist = $distinct ? 'DISTINCT ': '';
    $f = "{$function}({$dist}{$textOrColumn})" . ( $alias != '' ? " AS $alias" : '' );
    return __makeFunction( $f );
}

// ----------------------------------------------------------------------------
// AGGREGATE FUNCTIONS
// ----------------------------------------------------------------------------

function count( $column, string $alias = '' ): string {
    return __makeAggregateFunction( 'COUNT', false, $column, $alias );
}

function countDistinct( $column, string $alias = '' ): string {
    return __makeAggregateFunction( 'COUNT', true, $column, $alias );
}

function sum( $column, string $alias = '' ): string {
    return __makeAggregateFunction( 'SUM', false, $column, $alias );
}

function sumDistinct( $column, string $alias = '' ): string {
    return __makeAggregateFunction( 'SUM', true, $column, $alias );
}

function avg( $column, string $alias = '' ): string {
    return __makeAggregateFunction( 'AVG', false, $column, $alias );
}

function avgDistinct( $column, string $alias = '' ): string {
    return __makeAggregateFunction( 'AVG', true, $column, $alias );
}

function min( $column, string $alias = '' ): string {
    return __makeAggregateFunction( 'MIN', false, $column, $alias );
}

function max( $column, string $alias = '' ): string {
    return __makeAggregateFunction( 'MAX', false, $column, $alias );
}

// ----------------------------------------------------------------------------
// BASIC FUNCTIONS
// ----------------------------------------------------------------------------

function select( ...$columns ): Select {
    return new Select( false, ...$columns );
}

function selectDistinct( ...$columns ): Select {
    return new Select( true, ...$columns );
}

function col( $name ): Column {
    $name = __parseColumnAndAlias( $name );
    return new Column( $name );
}

function val( $value ): Value {
    if ( $value instanceof DateTimeInterface ) {
        return new Value( __toDateString( $value ) );
    }
    return new Value( $value );
}

function quote( $value ): string {
    return __toString( $value );
}

function wrap( Condition $c ): Condition {
    return new Wrap( $c );
}

// function alias( string $column, string $alias ): string {
//     return "$column AS $alias";
// }

// ----------------------------------------------------------------------------
// UTILITIES
// ----------------------------------------------------------------------------

function desc( string $column ): string {
    return $column . ' DESC';
}

function asc( string $column ): string {
    return $column . ' ASC';
}

// ----------------------------------------------------------------------------
// DATE AND TIME FUNCTIONS
// ----------------------------------------------------------------------------

function now(): Value {
    $f = match ( DB::$type ) {
        DBType::SQLITE => "DATETIME('now')",
        DBType::ORACLE => 'SYSDATE',
        DBType::SQLSERVER => 'CURRENT_TIMESTAMP',
        default => 'NOW()' // MySQL, PostgreSQL
    };
    return __makeFunction( $f );
}


function date(): Value {
    $f = match ( DB::$type ) {
        DBType::ORACLE => 'SYSDATE',
        DBType::SQLSERVER => 'GETDATE()',
        default => 'CURRENT_DATE' // MySQL, PostgreSQL, SQLite
    };
    return __makeFunction( $f );
}


function time(): Value {
    $f = match ( DB::$type ) {
        DBType::ORACLE, DBType::SQLSERVER => 'CURRENT_TIMESTAMP',
        default => 'CURRENT_TIME' // MySQL, PostgreSQL, SQLite
    };
    return __makeFunction( $f );
}


function extract( string $unit, string $date ): Value {
    $date = __toString( $date );
    $f = match ( DB::$type ) {
        DBType::SQLSERVER => "DATEPART($unit, $date)",
        DBType::SQLITE => "strftime('%{$unit}', $date)",
        default => "EXTRACT($unit FROM $date)" // MySQL, PostgreSQL, Oracle
    };
    return __makeFunction( $f );
}

function diffInDays( string $startDate, string $endDate ): Value {
    $startDate = __toString( $startDate );
    $endDate = __toString( $endDate );
    $f = match ( DB::$type ) {
        DBType::ORACLE, DBType::POSTGRESQL, DBType::SQLITE => "$endDate - $startDate",
        DBType::SQLSERVER => "DATEDIFF(day, $startDate, $endDate)",
        default => "DATEDIFF($startDate, $endDate)" // MySQL
    };
    return __makeFunction( $f );
}


function addDays( string $dateOrColumn, int|string $value ): Value {
    $unit = match ( DB::$type ) {
        DBType::POSTGRESQL => 'days',
        default => 'day'
    };
    return dateAdd( $dateOrColumn, $value, $unit );
}

function subDays( string $dateOrColumn, int|string $value ): Value {
    $unit = match ( DB::$type ) {
        DBType::POSTGRESQL => 'days',
        default => 'day'
    };
    return dateSub( $dateOrColumn, $value, $unit );
}

function dateAdd( string $dateOrColumn, int|string $value, string $unit = 'day' ): Value {
    $dateOrColumn = __toString( $dateOrColumn );
    $unit = DB::$type === DBType::MYSQL ? strtoupper( $unit ) : strtolower( $unit );
    $f = match ( DB::$type ) {
        DBType::ORACLE => "$dateOrColumn + $value",
        DBType::SQLSERVER => "DATEADD($unit, $value, $dateOrColumn)",
        DBType::SQLITE => "DATE($dateOrColumn, +{$value} $unit",
        DBType::POSTGRESQL => "$dateOrColumn + INTERVAL $value $unit",
        default => "DATE_ADD($dateOrColumn, INTERVAL $value $unit)" // MySQL
    };
    return __makeFunction( $f );
}

function dateSub( string $dateOrColumn, int|string $value, string $unit = 'day' ): Value {
    $dateOrColumn = __toString( $dateOrColumn );
    $unit = DB::$type === DBType::MYSQL ? strtoupper( $unit ) : strtolower( $unit );
    $f = match ( DB::$type ) {
        DBType::ORACLE => "$dateOrColumn - $value",
        DBType::SQLSERVER => "DATESUB($unit, $value, $dateOrColumn)",
        DBType::SQLITE => "DATE($dateOrColumn, -{$value} $unit",
        DBType::POSTGRESQL => "$dateOrColumn - INTERVAL $value $unit",
        default => "DATE_SUB($dateOrColumn, INTERVAL $value $unit)" // MySQL
    };
    return __makeFunction( $f );
}

// ----------------------------------------------------------------------------
// STRING FUNCTIONS
// ----------------------------------------------------------------------------

function upper( $textOrColumn ): Value {
    $textOrColumn = __valueOrName( $textOrColumn );
    return __makeFunction( "UPPER($textOrColumn)" );
}

function lower( $textOrColumn ): Value {
    $textOrColumn = __valueOrName( $textOrColumn );
    return __makeFunction( "LOWER($textOrColumn)" );
}

function substring( $textOrColumn, int|string $pos = 1, int $len = 0 ): Value {
    $textOrColumn = __valueOrName( $textOrColumn );
    $f = match ( DB::$type ) {
        DBType::POSTGRESQL => ( $len > 0 ? "SUBSTRING($textOrColumn FROM $pos FOR $len)" : "SUBSTRING($textOrColumn FROM $pos)" ),
        DBType::SQLITE, DBType::ORACLE => ( $len > 0 ? "SUBSTR($textOrColumn, $pos, $len)" : "SUBSTR($textOrColumn, $pos)" ),
        DBType::SQLSERVER => "SUBSTRING($textOrColumn, $pos, $len)",
        default => ( $len > 0 ? "SUBSTRING($textOrColumn, $pos, $len)" : "SUBSTRING($textOrColumn, $pos)" ) // MySQL
    };
    return __makeFunction( $f );
}


function concat( $textOrColumn1, $textOrColumn2, ...$other ): string|Value {
    $textOrColumn1 = __valueOrName( $textOrColumn1 );
    $textOrColumn2 = __valueOrName( $textOrColumn2 );
    $other = array_map( fn( $s ) => __valueOrName( $s ), $other );

    if ( DB::$type === DBType::ORACLE ) {
        return implode( ' || ', [ $textOrColumn1, $textOrColumn2, ...$other ] );
    }

    $params = implode( ', ', [ $textOrColumn1, $textOrColumn2, ...$other ] );
    return __makeFunction( "CONCAT($params)" );
}


function length( $textOrColumn ): Value {
    $textOrColumn = __valueOrName( $textOrColumn );
    $f = match ( DB::$type ) {
        DBType::SQLSERVER => "LEN($textOrColumn)",
        DBType::MYSQL, DBType::POSTGRESQL => "CHAR_LENGTH($textOrColumn)",
        default => "LENGTH($textOrColumn)"
    };
    return __makeFunction( $f );
}


function bytes( $textOrColumn ): Value {
    $textOrColumn = __valueOrName( $textOrColumn );
    $f = match ( DB::$type ) {
        DBType::SQLSERVER => "LEN($textOrColumn)",
        default => "LENGTH($textOrColumn)"
    };
    return __makeFunction( $f );
}

// ----------------------------------------------------------------------------
// NULL HANDLING FUNCTIONS
// ----------------------------------------------------------------------------

function ifNull( $valueOrColumm, $valueOrColumnIfNull ): Value {
    $valueOrColumm = __valueOrName( $valueOrColumm );
    $valueOrColumnIfNull = __valueOrName( $valueOrColumnIfNull );
    $f = match ( DB::$type ) {
        DBType::POSTGRESQL, DBType::MYSQL => "COALESCE($valueOrColumm, $valueOrColumnIfNull)",
        DBType::ORACLE => "NVL($valueOrColumm,$valueOrColumnIfNull)",
        default => "IFNULL($valueOrColumm, $valueOrColumnIfNull)"
    };
    return __makeFunction( $f );
}

// ----------------------------------------------------------------------------
// MATHEMATICAL FUNCTIONS
// ----------------------------------------------------------------------------

function abs( $valueOrColumn ): Value {
    $valueOrColumn = __valueOrName( $valueOrColumn );
    return __makeFunction( "ABS($valueOrColumn)" );
}

function round( $valueOrColumn, int $decimals ): Value {
    $valueOrColumn = __valueOrName( $valueOrColumn );
    return __makeFunction( "ROUND($valueOrColumn, $decimals)" );
}

function ceil( $valueOrColumn ): Value {
    $valueOrColumn = __valueOrName( $valueOrColumn );
    $f = match( DB::$type ) {
        DBType::SQLSERVER => "CEILING($valueOrColumn)",
        default => "CEIL($valueOrColumn)"
    };
    return __makeFunction( $f );
}

function floor( $valueOrColumn ): Value {
    $valueOrColumn = __valueOrName( $valueOrColumn );
    return __makeFunction( "FLOOR($valueOrColumn)" );
}

function power( $base, $exponent ): Value {
    $base = __valueOrName( $base );
    $exponent = __valueOrName( $exponent );
    return __makeFunction( "POWER($base, $exponent)" );
}


function sqrt( $valueOrColumn ): Value {
    $valueOrColumn = __valueOrName( $valueOrColumn );
    return __makeFunction( "SQRT($valueOrColumn)" );
}

function sin( $valueOrColumn ): Value {
    $valueOrColumn = __valueOrName( $valueOrColumn );
    return __makeFunction( "SIN($valueOrColumn)" );
}

function cos( $valueOrColumn ): Value {
    $valueOrColumn = __valueOrName( $valueOrColumn );
    return __makeFunction( "COS($valueOrColumn)" );
}

function tan( $valueOrColumn ): Value {
    $valueOrColumn = __valueOrName( $valueOrColumn );
    return __makeFunction( "TAN($valueOrColumn)" );
}
