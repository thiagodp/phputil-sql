<?php
namespace phputil\sql;

use \DateTimeInterface;
use \Stringable; // PHP 8.0+

// ----------------------------------------------------------------------------
// Types
// ----------------------------------------------------------------------------

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

// ----------------------------------------------------------------------------

interface DBStringable {

    public function toString( DBType $dbType = DBType::NONE ): string;
}

interface Condition extends DBStringable {

    public function and( Condition $other ): Condition;
    public function andNot( Condition $other ): Condition;
    public function or( Condition $other ): Condition;
    public function orNot( Condition $other ): Condition;

}

// ----------------------------------------------------------------------------
// Conditional Operators
// ----------------------------------------------------------------------------

// NEW
class ConditionalOp implements Condition {

    public function __construct(
        protected string $operator,
        protected $leftSide,
        protected $rightSide
    ) {
    }

    public function and( Condition $other ): Condition {
        return new ConditionalOp( 'AND', $this, $other );
    }

    public function andNot( Condition $other ): Condition {
        return new ConditionalOp( 'AND NOT', $this, $other );
    }

    public function or( Condition $other ): Condition {
        return new ConditionalOp( 'OR', $this, $other );
    }

    public function orNot( Condition $other ): Condition {
        return new ConditionalOp( 'OR NOT', $this, $other );
    }


    protected function convertToString( $side, DBType $dbType = DBType::NONE ): string {
        if ( $side instanceof Column ) {
            $side = __valueOrName( $side->toString( $dbType ), $dbType );
        } else
        // if ( $side instanceof ConditionalOp ) {
        //     $side = $side->toString( $dbType );
        // } else
        if ( $side instanceof From ) {
            $side = $side->endAsString( $dbType );
        } else if ( is_array( $side ) ) {
            $side = array_map( fn( $x ) => __toValue( $x, $dbType ), $side );
            $side = implode( ', ', $side );
        } else {
            $side = __toValue( $side, $dbType );
        }
        return $side;
    }

    public function toString( DBType $dbType = DBType::NONE ): string {
        $leftSide = $this->convertToString( $this->leftSide, $dbType );
        $rightSide = $this->convertToString( $this->rightSide, $dbType );
        return $leftSide . ' ' . $this->operator . ' ' . $rightSide;
    }
}
// ----------------------------------------------------------------------------
// BETWEEN
// ----------------------------------------------------------------------------

class BetweenCondition extends ConditionalOp {

    public function __construct(
        protected $columnName,
        $leftSide,
        $rightSide
    ) {
        parent::__construct( 'AND', $leftSide, $rightSide );
    }

    public function toString( DBType $dbType = DBType::NONE ): string {
        if ( is_object( $this->columnName ) && $this->columnName instanceof Column ) {
            $column = $this->columnName->toString( $dbType );
        } else {
            $column = __asName( $this->columnName, $dbType );
        }
        return $column . ' BETWEEN ' . parent::toString( $dbType );
    }

}

// ----------------------------------------------------------------------------
// Comparison Operators
// ----------------------------------------------------------------------------
class InCondition extends ConditionalOp {

    public function __construct($leftSide, $rightSide) {
        parent::__construct( 'IN', $leftSide, $rightSide );
    }

    public function toString( DBType $dbType = DBType::NONE ): string {
        $left = $this->convertToString( $this->leftSide, $dbType );
        $right = $this->convertToString( $this->rightSide, $dbType );
        return $left . ' ' . $this->operator . ' (' . $right . ')';
    }
}

// ----------------------------------------------------------------------------

class ComparableContent implements DBStringable {

    /**
     * @var Condition[] $conditions
     */
    protected $conditions = [];

    public function __construct(
        public $content
    ) {
    }

    protected function add( Condition $c ): Condition {
        $this->conditions []= $c;
        return $c;
    }

    public function equalTo( $rightSide ): Condition {
        return $this->add(
            new ConditionalOp( '=', $this->content, $rightSide )
        );
    }

    public function notEqualTo( $rightSide ): Condition {
        return $this->add(
            new ConditionalOp( '<>', $this->content, $rightSide )
        );
    }

    /** Alias for `notEqualTo()` */
    public function differentFrom( $rightSide ): Condition {
        return $this->notEqualTo( $rightSide );
    }

    public function greaterThan( $rightSide ): Condition {
        return $this->add(
            new ConditionalOp( '>', $this->content, $rightSide )
        );
    }

    public function greaterThanOrEqualTo( $rightSide ): Condition {
        return $this->add(
            new ConditionalOp( '>=', $this->content, $rightSide )
        );
    }

    public function lessThan( $rightSide ): Condition {
        return $this->add(
            new ConditionalOp( '<', $this->content, $rightSide )
        );
    }

    public function lessThanOrEqualTo( $rightSide ): Condition {
        return $this->add(
            new ConditionalOp( '<=', $this->content, $rightSide )
        );
    }

    public function like( $rightSide ): Condition {
        return $this->add(
            new ConditionalOp( 'LIKE',  $this->content, $rightSide )
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
            new ConditionalOp( 'IS', $this->content, new Value( 'NULL' ) )
        );
    }

    public function isNotNull(): Condition {
        return $this->add(
            new ConditionalOp( 'IS', $this->content, new Value( 'NOT NULL' ) )
        );
    }

    public function isTrue(): Condition {
        return $this->add(
            new ConditionalOp( 'IS', $this->content, new Value( 'TRUE' ) )
        );
    }

    public function isFalse(): Condition {
        return $this->add(
            new ConditionalOp( 'IS', $this->content, new Value( 'FALSE' ) )
        );
    }

    public function toString( DBType $dbType = DBType::NONE ): string {

        $content = __parseColumnAndAlias( $this->content, $dbType );
        if ( empty( $this->conditions ) ) {
            return $content;
        }
        return $content . ' ' . __conditionsToString( $this->conditions, $dbType );
    }
}

class Column implements DBStringable {

    public function __construct(
        public string $name
    ) {
    }

    public function toString( DBType $dbType = DBType::NONE ): string {
        return __parseColumnAndAlias( $this->name, $dbType );
    }
}

class Value implements DBStringable {

    public function __construct(
        public $content
    ) {
    }

    public function toString( DBType $dbType = DBType::NONE ): string {
        return __toValue( $this->content, $dbType );
    }
}


class Expression implements DBStringable {

    public function __construct(
        public string $name,
        public bool $isFunction = false,
        public $arg = '',
        public string $alias = ''
    ) {
    }

    public function toString( DBType $dbType = DBType::NONE ): string {

        $alias = '';
        if ( ! empty( $this->alias ) ) {
            $alias = __asName( $this->alias, $dbType );
            $alias = ' AS ' . $alias;
        }

        $arg = __valueOrName( $this->arg, $dbType );

        if ( $this->isFunction ) {
            return $this->name . '(' . $arg . ')' . $alias;
        }
        return $this->name . $arg . $alias;
    }
}


class Func implements DBStringable {

    public function __construct(
        public string $functionName,
        public bool $distinct,
        public $valueOrColumn,
        public string $alias = ''
    ) {
    }

    public function as( string $alias ): Func {
        $this->alias = $alias;
        return $this;
    }

    public function toString( DBType $dbType = DBType::NONE ): string {
        $valueOrColumn = __valueOrName( $this->valueOrColumn, $dbType );
        $alias = __asName( $this->alias, $dbType );
        $dist = $this->distinct ? 'DISTINCT ': '';
        $f = "{$this->functionName}({$dist}{$valueOrColumn})" . ( $alias != '' ? " AS $alias" : '' );
        return $f;
    }

}


abstract class OnDemandFunction implements DBStringable {

    protected string $aliasValue = '';

    public function as( string $alias ): OnDemandFunction {
        $this->aliasValue = $alias;
        return $this;
    }
}


//=====


class Select implements DBStringable, Stringable {

    protected array $columns;
    protected ?From $from = null;

    /**
     * Constructor
     *
     * @param bool $distinct
     * @param string[] $columns
     */
    public function __construct(
        protected bool $distinct,
        ...$columns
    ) {
        // OLD:
        // if ( empty( $columns ) ) {
        //     $columns = [ '*' ];
        // }
        // $this->columns = array_map( fn($c) => __parseColumnAndAlias( $c ), $columns );

        $this->columns = $columns;
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
        $tableData = array_map( fn( $t ) => new TableData( $t ), $tables );
        $this->from = new From( $this, $tableData );
        return $this->from;
    }


    public function toString( DBType $dbType = DBType::NONE ): string {

        $columns = [];
        if ( empty( $this->columns ) ) {
            $columns = [ '*' ];
        } else {
            $columns = array_map( fn($c) => __parseColumnAndAlias( $c, $dbType ), $this->columns );
        }
        $from = $this->from ? $this->from->toString( $dbType ) : '';
        return 'SELECT ' . ( $this->distinct ? 'DISTINCT ' : '' ) . implode( ', ', $columns ) . $from;
    }


    public function __toString(): string {
        return $this->toString( DB::$type ); // Uses the database type set as default
    }
}


class TableData implements DBStringable {

    public function __construct(
        public readonly string $table
    ) {
    }

    public function toString( DBType $dbType = DBType::NONE ): string {

        $pieces = __parseSeparatedValues( $this->table );
        $tableName = __asName( $pieces[ 0 ] ?? '', $dbType );
        $tableAlias = __asName( $pieces[ 1 ] ?? '', $dbType );

        // ---------------------------------------------------------------------

        if ( $tableAlias != '' ) { // It has an alias
            return $tableName . ' ' . $tableAlias;
        }
        return $tableName;
    }
}


class From implements DBStringable {

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
        $j = new Join( $this, $table, $type );
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

    public function endAsString( DBType $dbType = DBType::NONE ): string {
        return $this->end()->toString( $dbType );
    }

    public function toString( DBType $dbType = DBType::NONE ): string {

        $tableNames = array_map( fn( $t ) => $t->toString( $dbType ), $this->tables );
        $s = ' FROM ' . implode( ', ', $tableNames );
        foreach ( $this->joins as $j ) {
            $s .= ' ' . $j->toString( $dbType );
        }

        $where = __conditionsToString( $this->whereConditions, $dbType );
        if ( $where != '' ) {
            $s .= ' WHERE' . $where;
        }

        $groupByColumns = array_map( fn($c) => __parseColumnAndAlias( $c, $dbType ), $this->groupByColumns );

        if ( ! empty( $groupByColumns ) ) {
            $s .= ' GROUP BY ' . implode( ', ', $groupByColumns );

            if ( $this->havingCondition ) {
                $s .= ' HAVING ' . $this->havingCondition->toString( $dbType );
            }
        }

        if ( ! empty( $this->columnOrderings )) {
            $orderings = array_map( fn( $c ) => $c->toString( $dbType ), $this->columnOrderings );
            $s .= ' ORDER BY ' . implode( ', ', $orderings );
        }

        $limitOffset = $this->makeLimitAndOffset( $dbType );
        if ( ! empty( $limitOffset ) ) {
            $s .= $limitOffset;
        }

        if ( $this->unionSelect !== null ) {
            $s .= ' UNION ' . ( $this->isUnionDistinct ? 'DISTINCT ' : '' ) . $this->unionSelect->toString( $dbType );
        }

        return $s;
    }
}




class Join implements DBStringable {

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

    public function toString( DBType $dbType = DBType::NONE ): string {

        $values = __parseSeparatedValues( $this->table );
        $declaration = __asName( $values[ 0 ], $dbType );
        if ( isset( $values[ 1 ] ) ) {
            $declaration .= ' ' . __asName( $values[ 1 ], $dbType );
        }

        return $this->type . ' ' . $declaration . ' ON ' . $this->condition->toString( $dbType );
    }
}


class ColumnOrdering implements DBStringable {

    public function __construct(
        protected string $column
    ) {
    }

    public function toString( DBType $dbType = DBType::NONE ): string {

        $pieces = __parseSeparatedValues( $this->column );
        $column = __parseColumnAndAlias( $pieces[ 0 ] ?? '', $dbType );
        $direction = strtoupper( $pieces[ 1 ] ?? 'ASC' ) == 'DESC' ? 'DESC' : 'ASC';

        return $column . ' ' . $direction;
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
    protected function makeLimitAndOffset( DBType $dbType = DBType::NONE ): string {

        // Compatible with: SQLServer, Oracle.

        if ( $dbType === DBType::ORACLE || $dbType === DBType::SQLSERVER ) {
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


class Wrap implements Condition {

    public function __construct(
        protected Condition $condition
    ) {
    }

    public function toString( DBType $dbType = DBType::NONE ): string {
        return '('. $this->condition->toString( $dbType ) . ')';
    }

    public function and( Condition $other ): Condition {
        return new ConditionalOp( 'AND', $this, $other );
    }

    public function andNot( Condition $other ): Condition {
        return new ConditionalOp( 'AND NOT', $this, $other );
    }

    public function or( Condition $other ): Condition {
        return new ConditionalOp( 'OR', $this, $other );
    }

    public function orNot( Condition $other ): Condition {
        return new ConditionalOp( 'OR NOT', $this, $other );
    }
}

// ----------------------------------------------------------------------------
// INTERNAL
// ----------------------------------------------------------------------------

function __getQuoteCharacters( DBType $dbType = DBType::NONE ): array {
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


function __conditionsToString( array $conditions, DBType $dbType ): string {
    $r = '';
    foreach ( $conditions as $c ) {
        $r .= ' ' . $c->toString( $dbType );
    }
    return $r;
}


function __parseColumnAndAlias( $column, DBType $dbType ): string {

    if ( $column instanceof ComparableContent ) {
        $column = $column->content;
    }

    if ( $column instanceof Value ) {
        return (string) $column->content;
    }

    if ( $column instanceof Expression || $column instanceof OnDemandFunction ) {
        return $column->toString( $dbType );
    }

    if ( $column instanceof DBStringable ) {
        $column = $column->toString( $dbType );
    }

    if ( ! is_string( $column ) ) {
        return (string) $column;
    }

    $regex = '/^[ ]*([^ ]+)[ ]*(?: AS )?[ ]*([^ ]+)?$/i';
    $matches = [];
    if ( preg_match( $regex, $column, $matches ) ) {
        $column = $matches[ 1 ];
        if ( ! is_numeric( $column ) ) {
            $pieces = explode( '.', $column );
            if ( isset( $pieces[ 1 ] ) ) { // Table plus column
                $table = __asName( $pieces[ 0 ], $dbType );
                $column = $table . '.' . __asName( $pieces[ 1 ], $dbType );
            } else {
                $column = __asName( $pieces[ 0 ], $dbType );
            }
        }
        if ( isset( $matches[ 2 ] ) ) {
            $alias = __asName( trim( $matches[ 2 ] ), $dbType );
            return $column . ' AS ' . $alias;
        }
    }
    return $column;
}

function __asName( string $name, DBType $dbType ): string {
    if ( $name === '*' ) { // Ignore quotes for a star
        return $name;
    }
    $quotes = __getQuoteCharacters( $dbType );
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

function __toValue( $value, DBType $dbType = DBType::NONE ) {

    if ( is_null( $value ) ) {
        return 'NULL';
    } else if ( is_string( $value ) ) {
        return __toString( $value );
    } else if ( $value instanceof Value ) {
        $content = $value->content;
        if ( is_string( $content ) || is_numeric( $content ) ) {
            return (string) $content;
        }
        return __toValue( $content, $dbType );
    } else if ( $value instanceof Column ) {
        return __asName( $value->toString( $dbType ), $dbType );
    } else if ( $value instanceof DBStringable ) {
        return $value->toString( $dbType );
    } else if ( $value instanceof DateTimeInterface ) {
        return __toDateString( $value );
    } else if ( is_bool( $value ) ) {
        return __toBoolean( $value, $dbType === DBType::ORACLE );
    } else if ( is_array( $value ) ) {
        return $value;
    }

    return "$value"; // to string
}

function __valueOrName( $str, DBType $dbType ): string {
    if ( $str instanceof Value ) {
        $str = __toValue( $str->content, $dbType );
    } else if ( is_string( $str ) ) {
        $str = __asName( $str, $dbType );
    } else if ( $str instanceof Column ) {
        $str = __asName( $str->name, $dbType );
    } else if ( $str instanceof ComparableContent ) {
        // $str = $str->toString( $dbType );
        $str = __valueOrName( $str->content, $dbType ); // Evaluates according to the content type
        // $str = __toValue( $str->content, $dbType );
    }
    return "$str";
}

// function __makeAggregateFunction( string $function, bool $distinct, $textOrColumn, string $alias = '' ): string {
//     $textOrColumn = __valueOrName( $textOrColumn );
//     $alias = __asName( $alias );
//     $dist = $distinct ? 'DISTINCT ': '';
//     $f = "{$function}({$dist}{$textOrColumn})" . ( $alias != '' ? " AS $alias" : '' );
//     return __makeFunction( $f );
// }

// ----------------------------------------------------------------------------
// AGGREGATE FUNCTIONS
// ----------------------------------------------------------------------------

function count( $column, string $alias = '' ): Func {
    return new Func( 'COUNT', false, $column, $alias );
}

function countDistinct( $column, string $alias = '' ): Func {
    return new Func( 'COUNT', true, $column, $alias );
}

function sum( $column, string $alias = '' ): Func {
    return new Func( 'SUM', false, $column, $alias );
}

function sumDistinct( $column, string $alias = '' ): Func {
    return new Func( 'SUM', true, $column, $alias );
}

function avg( $column, string $alias = '' ): Func {
    return new Func( 'AVG', false, $column, $alias );
}

function avgDistinct( $column, string $alias = '' ): Func {
    return new Func( 'AVG', true, $column, $alias );
}

function min( $column, string $alias = '' ): Func {
    return new Func( 'MIN', false, $column, $alias );
}

function max( $column, string $alias = '' ): Func {
    return new Func( 'MAX', false, $column, $alias );
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

function col( $name ): ComparableContent {
    return new ComparableContent( new Column( $name ) );
}

function val( $value ): ComparableContent {
    $v = null;
    if ( $value instanceof DateTimeInterface ) {
        $v = new Value( __toDateString( $value ) );
    } else {
        $v = new Value( $value );
    }
    return new ComparableContent( $v );
}

function param( $value = '?' ): ComparableContent {
    $value = trim( $value );
    if ( $value === '' || $value === ':' ) {
        $value = '?';
    } else if ( $value != '?' && ! str_starts_with( $value, ':' ) ) {
        $value = ':' . $value;
    }
    return val( $value );
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

function now(): OnDemandFunction {
    return new class extends OnDemandFunction {

        public function toString( DBType $dbType = DBType::NONE ): string {
            $e = match ( $dbType ) {
                DBType::SQLITE => new Expression( 'DATETIME', true, "'now'", $this->aliasValue ),
                DBType::ORACLE => new Expression( 'SYSDATE', false, '', $this->aliasValue  ),
                DBType::SQLSERVER => new Expression( 'CURRENT_TIMESTAMP', false, '', $this->aliasValue  ),
                default => new Expression( 'NOW', true, '', $this->aliasValue ) // MySQL, PostgreSQL
            };
            return $e->toString( $dbType );
        }

    };
}


function date(): OnDemandFunction {
    return new class extends OnDemandFunction {

        public function toString( DBType $dbType = DBType::NONE ): string {
            $e = match ( $dbType ) {
                DBType::ORACLE => new Expression( 'SYSDATE', false ),
                DBType::SQLSERVER => new Expression( 'GETDATE', true ),
                default => new Expression( 'CURRENT_DATE', false ) // MySQL, PostgreSQL, SQLite
            };
            return $e->toString( $dbType );
        }

    };
}


function time(): OnDemandFunction {
    return new class extends OnDemandFunction {

        public function toString( DBType $dbType = DBType::NONE ): string {
            $e = match ( $dbType ) {
                DBType::ORACLE, DBType::SQLSERVER => new Expression( 'CURRENT_TIMESTAMP', false ),
                default => new Expression( 'CURRENT_TIME', false ) // MySQL, PostgreSQL, SQLite
            };
            return $e->toString( $dbType );
        }

    };
}

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

class ExtractFunction extends OnDemandFunction {

    protected $dateOrColumn;

    public function __construct( protected Extract $unit ) {}


    public function from( $dateOrColumn ): ExtractFunction {
        $this->dateOrColumn = $dateOrColumn;
        return $this;
    }

    public function toString( DBType $dbType = DBType::NONE ): string {

        $unit = $this->unit->name;
        if ( $dbType === DBType::SQLITE ) {
            $unit = match ( $this->unit ) {
                Extract::YEAR => '%Y',
                Extract::MONTH => '%m',
                Extract::DAY => '%d',

                Extract::HOUR => '%H',
                Extract::MINUTE => '%M',
                Extract::SECOND => '%S',
                Extract::MICROSECOND => '%f',

                Extract::QUARTER => '%',
                Extract::WEEK => '%W',
                Extract::WEEK_DAY => '%w',

                default => '%?'
            };
        }

        $date = __valueOrName( $this->dateOrColumn, $dbType );
        return match ( $dbType ) {
            DBType::SQLSERVER => new Expression( 'DATEPART', true, "$unit, $date" ),
            DBType::SQLITE => "strftime('%{$unit}', $date)",
            default => "EXTRACT($unit FROM $date)" // MySQL, PostgreSQL, Oracle
        };
    }
}


function extract( Extract $unit, $dateOrColumn = '' ): ExtractFunction {
    if ( empty( $dateOrColumn ) ) {
        return ( new ExtractFunction( $unit ) )->from( val( '' ) );
    }
    return ( new ExtractFunction( $unit ) )->from( $dateOrColumn );
}

function diffInDays( string $startDate, string $endDate ): OnDemandFunction {
    return new class ( $startDate, $endDate ) extends OnDemandFunction {

        public function __construct( protected string $startDate, protected string $endDate ) {}

        public function toString( DBType $dbType = DBType::NONE ): string {
            $startDate = __toString( $this->startDate );
            $endDate = __toString( $this->endDate );
            return match ( $dbType ) {
                DBType::ORACLE, DBType::POSTGRESQL, DBType::SQLITE => "$endDate - $startDate",
                DBType::SQLSERVER => "DATEDIFF(day, $startDate, $endDate)",
                default => "DATEDIFF($startDate, $endDate)" // MySQL
            };
        }
    };
}

function addDays( string $dateOrColumn, int|string $value ): OnDemandFunction {
    return new class ( $dateOrColumn, $value ) extends OnDemandFunction {

        public function __construct( protected string $dateOrColumn, protected string $value ) {}

        public function toString( DBType $dbType = DBType::NONE ): string {
            $unit = match ( $dbType ) {
                DBType::POSTGRESQL => 'days',
                default => 'day'
            };
            return dateAdd( $this->dateOrColumn, $this->value, $unit )->toString( $dbType );
        }
    };
}

function subDays( string $dateOrColumn, int|string $value ): OnDemandFunction {
    return new class ( $dateOrColumn, $value ) extends OnDemandFunction {

        public function __construct( protected string $dateOrColumn, protected string $value ) {}

        public function toString( DBType $dbType = DBType::NONE ): string {
            $unit = match ( $dbType ) {
                DBType::POSTGRESQL => 'days',
                default => 'day'
            };
            return dateSub( $this->dateOrColumn, $this->value, $unit )->toString( $dbType );
        }
    };
}

function dateAdd( string $dateOrColumn, int|string $value, string $unit = 'day' ): OnDemandFunction {
    return new class ( $dateOrColumn, $value, $unit ) extends OnDemandFunction {

        public function __construct( protected string $dateOrColumn, protected string $value, protected string $unit ) {}

        public function toString( DBType $dbType = DBType::NONE ): string {
            $dateOrColumn = __toString( $this->dateOrColumn );
            $value = $this->value;
            $unit = $dbType === DBType::MYSQL ? strtoupper( $this->unit ) : strtolower( $this->unit );
            return match ( $dbType ) {
                DBType::ORACLE => "$dateOrColumn + $value",
                DBType::SQLSERVER => "DATEADD($unit, $value, $dateOrColumn)",
                DBType::SQLITE => "DATE($dateOrColumn, +{$value} $unit",
                DBType::POSTGRESQL => "$dateOrColumn + INTERVAL $value $unit",
                default => "DATE_ADD($dateOrColumn, INTERVAL $value $unit)" // MySQL
            };
        }
    };
}

function dateSub( string $dateOrColumn, int|string $value, string $unit = 'day' ): OnDemandFunction {
    return new class ( $dateOrColumn, $value, $unit ) extends OnDemandFunction {

        public function __construct( protected string $dateOrColumn, protected string $value, protected string $unit ) {}

        public function toString( DBType $dbType = DBType::NONE ): string {
            $dateOrColumn = __toString( $this->dateOrColumn );
            $value = $this->value;
            $unit = $dbType === DBType::MYSQL ? strtoupper( $this->unit ) : strtolower( $this->unit );
            return match ( $dbType ) {
                DBType::ORACLE => "$dateOrColumn - $value",
                DBType::SQLSERVER => "DATESUB($unit, $value, $dateOrColumn)",
                DBType::SQLITE => "DATE($dateOrColumn, -{$value} $unit",
                DBType::POSTGRESQL => "$dateOrColumn - INTERVAL $value $unit",
                default => "DATE_SUB($dateOrColumn, INTERVAL $value $unit)" // MySQL
            };
        }
    };
}

// ----------------------------------------------------------------------------
// STRING FUNCTIONS
// ----------------------------------------------------------------------------

function upper( $textOrColumn ): OnDemandFunction {
    return new class ( $textOrColumn ) extends OnDemandFunction {

        public function __construct( protected $textOrColumn ) {}

        public function toString( DBType $dbType = DBType::NONE ): string {
            $textOrColumn = __valueOrName( $this->textOrColumn, $dbType );
            return "UPPER($textOrColumn)";
        }
    };
}

function lower( $textOrColumn ): OnDemandFunction {
    return new class ( $textOrColumn ) extends OnDemandFunction {

        public function __construct( protected $textOrColumn ) {}

        public function toString( DBType $dbType = DBType::NONE ): string {
            $textOrColumn = __valueOrName( $this->textOrColumn, $dbType );
            return "LOWER($textOrColumn)";
        }
    };
}

function substring( $textOrColumn, int|string $pos = 1, int $len = 0 ): OnDemandFunction {
    return new class ( $textOrColumn, $pos, $len ) extends OnDemandFunction {

        public function __construct( protected $textOrColumn, protected int|string $pos = 1, protected int $len = 0 ) {}

        public function toString( DBType $dbType = DBType::NONE ): string {
            $textOrColumn = __valueOrName( $this->textOrColumn, $dbType );
            $pos = $this->pos;
            $len = $this->len;
            return match ( $dbType ) {
                DBType::POSTGRESQL => ( $len > 0 ? "SUBSTRING($textOrColumn FROM $pos FOR $len)" : "SUBSTRING($textOrColumn FROM $pos)" ),
                DBType::SQLITE, DBType::ORACLE => ( $len > 0 ? "SUBSTR($textOrColumn, $pos, $len)" : "SUBSTR($textOrColumn, $pos)" ),
                DBType::SQLSERVER => "SUBSTRING($textOrColumn, $pos, $len)",
                default => ( $len > 0 ? "SUBSTRING($textOrColumn, $pos, $len)" : "SUBSTRING($textOrColumn, $pos)" ) // MySQL
            };
        }
    };
}


function concat( $textOrColumn1, $textOrColumn2, ...$other ): OnDemandFunction {
    return new class (  $textOrColumn1, $textOrColumn2, ...$other ) extends OnDemandFunction {

        protected $other;

        public function __construct( protected $textOrColumn1, protected $textOrColumn2, ...$other ) {
            $this->other = $other;
        }

        public function toString( DBType $dbType = DBType::NONE ): string {
            $textOrColumn1 = __valueOrName( $this->textOrColumn1, $dbType );
            $textOrColumn2 = __valueOrName( $this->textOrColumn2, $dbType );
            $other = array_map( fn( $s ) => __valueOrName( $s, $dbType ), $this->other );

            if ( $dbType === DBType::ORACLE ) {
                return implode( ' || ', [ $textOrColumn1, $textOrColumn2, ...$other ] );
            }

            $params = implode( ', ', [ $textOrColumn1, $textOrColumn2, ...$other ] );
            return "CONCAT($params)";
        }
    };
}


function length( $textOrColumn ): OnDemandFunction {
    return new class ( $textOrColumn ) extends OnDemandFunction {

        public function __construct( protected $textOrColumn ) {}

        public function toString( DBType $dbType = DBType::NONE ): string {
            $textOrColumn = __valueOrName( $this->textOrColumn, $dbType );
            return match ( $dbType ) {
                DBType::SQLSERVER => "LEN($textOrColumn)",
                DBType::MYSQL, DBType::POSTGRESQL => "CHAR_LENGTH($textOrColumn)",
                default => "LENGTH($textOrColumn)"
            };
        }
    };
}


function bytes( $textOrColumn ): OnDemandFunction {
    return new class ( $textOrColumn ) extends OnDemandFunction {

        public function __construct( protected $textOrColumn ) {}

        public function toString( DBType $dbType = DBType::NONE ): string {
            $textOrColumn = __valueOrName( $this->textOrColumn, $dbType );
            return match ( $dbType ) {
                DBType::SQLSERVER => "LEN($textOrColumn)",
                default => "LENGTH($textOrColumn)"
            };
        }
    };
}

// ----------------------------------------------------------------------------
// NULL HANDLING FUNCTIONS
// ----------------------------------------------------------------------------

function ifNull( $valueOrColumm, $valueOrColumnIfNull ): OnDemandFunction {
    return new class ( $valueOrColumm, $valueOrColumnIfNull ) extends OnDemandFunction {

        public function __construct( protected $valueOrColumm, protected $valueOrColumnIfNull ) {}

        public function toString( DBType $dbType = DBType::NONE ): string {
            $valueOrColumm = __valueOrName( $this->valueOrColumm, $dbType );
            $valueOrColumnIfNull = __valueOrName( $this->valueOrColumnIfNull, $dbType );
            return match ( $dbType ) {
                DBType::POSTGRESQL, DBType::MYSQL => "COALESCE($valueOrColumm, $valueOrColumnIfNull)",
                DBType::ORACLE => "NVL($valueOrColumm,$valueOrColumnIfNull)",
                default => "IFNULL($valueOrColumm, $valueOrColumnIfNull)"
            };
        }
    };
}

// ----------------------------------------------------------------------------
// MATHEMATICAL FUNCTIONS
// ----------------------------------------------------------------------------

class ValueOrColumnBasedOnDemandFunction extends OnDemandFunction {
    public function __construct( protected $functionName, protected $valueOrColumn ) {}

    public function toString( DBType $dbType = DBType::NONE ): string {
        $valueOrColumn = __valueOrName( $this->valueOrColumn, $dbType );
        return $this->functionName . '(' . $valueOrColumn . ')';
    }
}


function abs( $valueOrColumn ): OnDemandFunction {
    return new ValueOrColumnBasedOnDemandFunction( 'ABS', $valueOrColumn );
}

function round( $valueOrColumn, int $decimals = 2 ): OnDemandFunction {
    return new class ( $valueOrColumn, $decimals ) extends OnDemandFunction {

        public function __construct( protected $valueOrColumn, protected int $decimals = 2 ) {}

        public function toString( DBType $dbType = DBType::NONE ): string {
            $valueOrColumn = __valueOrName( $this->valueOrColumn, $dbType );
            $decimals = $this->decimals;
            return "ROUND($valueOrColumn, $decimals)";
        }
    };
}

function ceil( $valueOrColumn ): OnDemandFunction {
    return new class ( $valueOrColumn ) extends OnDemandFunction {

        public function __construct( protected $valueOrColumn ) {}

        public function toString( DBType $dbType = DBType::NONE ): string {
            $valueOrColumn = __valueOrName( $this->valueOrColumn, $dbType );
            return match( $dbType ) {
                DBType::SQLSERVER => "CEILING($valueOrColumn)",
                default => "CEIL($valueOrColumn)"
            };
        }
    };
}

function floor( $valueOrColumn ): OnDemandFunction {
    return new ValueOrColumnBasedOnDemandFunction( 'FLOOR', $valueOrColumn );
}

function power( $base, $exponent ): OnDemandFunction {
    return new class ( $base, $exponent ) extends OnDemandFunction {

        public function __construct( protected $base, protected $exponent ) {}

        public function toString( DBType $dbType = DBType::NONE ): string {
            $base = __valueOrName( $this->base, $dbType );
            $exponent = __valueOrName( $this->exponent, $dbType );
            return "POWER($base, $exponent)";
        }
    };
}

function sqrt( $valueOrColumn ): OnDemandFunction {
    return new ValueOrColumnBasedOnDemandFunction( 'SQRT', $valueOrColumn );
}

function sin( $valueOrColumn ): OnDemandFunction {
    return new ValueOrColumnBasedOnDemandFunction( 'SIN', $valueOrColumn );
}

function cos( $valueOrColumn ): OnDemandFunction {
    return new ValueOrColumnBasedOnDemandFunction( 'COS', $valueOrColumn );
}

function tan( $valueOrColumn ): OnDemandFunction {
    return new ValueOrColumnBasedOnDemandFunction( 'TAN', $valueOrColumn );
}
