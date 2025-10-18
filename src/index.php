<?php
namespace phputil\sql;

use \DateTimeInterface;
use \Stringable; // PHP 8.0+

// ----------------------------------------------------------------------------
// Types
// ----------------------------------------------------------------------------

enum SQLType: string {
    case NONE = 'none';
    case MYSQL = 'mysql';
    case SQLITE = 'sqlite';
    case ORACLE = 'oracle';
    case POSTGRESQL = 'postgresql';
    case SQLSERVER = 'sqlserver';
}

class SQL {
    public static SQLType $type = SQLType::NONE;

    public static function useNone(): void { self::$type = SQLType::NONE; }
    public static function useMySQL(): void { self::$type = SQLType::MYSQL; }
    public static function useSQLite(): void { self::$type = SQLType::SQLITE; }
    public static function usePostgreSQL(): void { self::$type = SQLType::POSTGRESQL; }
    public static function useSQLServer(): void { self::$type = SQLType::SQLSERVER; }
    public static function useOracle(): void { self::$type = SQLType::ORACLE; }
}

// ----------------------------------------------------------------------------

interface DBStringable {

    public function toString( SQLType $sqlType = SQLType::NONE ): string;
}

/** Chainable condition. */
interface Condition extends DBStringable {

    public function and( Condition $other ): Condition;
    public function andNot( Condition $other ): Condition;
    public function or( Condition $other ): Condition;
    public function orNot( Condition $other ): Condition;

}

// ----------------------------------------------------------------------------
// Conditional Operators
// ----------------------------------------------------------------------------

class ConditionalOp implements Condition {

    public function __construct(
        protected string $operator,
        protected mixed $leftSide,
        protected mixed $rightSide
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


    protected function convertToString( mixed $side, SQLType $sqlType = SQLType::NONE ): string {
        if ( $side instanceof Column ) {
            $side = __valueOrName( $side->toString( $sqlType ), $sqlType );
        } else if ( $side instanceof From ) {
            $side = $side->endAsString( $sqlType );
        } else {
            $side = __toValue( $side, $sqlType );
        }
        return $side;
    }

    public function toString( SQLType $sqlType = SQLType::NONE ): string {
        $leftSide = $this->convertToString( $this->leftSide, $sqlType );
        $rightSide = $this->convertToString( $this->rightSide, $sqlType );
        return $leftSide . ' ' . $this->operator . ' ' . $rightSide;
    }
}

// ----------------------------------------------------------------------------
// BETWEEN
// ----------------------------------------------------------------------------

class BetweenCondition extends ConditionalOp {

    public function __construct(
        protected string|Column $columnName,
        mixed $leftSide,
        mixed $rightSide
    ) {
        parent::__construct( 'AND', $leftSide, $rightSide );
    }

    public function toString( SQLType $sqlType = SQLType::NONE ): string {
        if ( is_object( $this->columnName ) ) {
            $column = $this->columnName->toString( $sqlType );
        } else if ( is_string( $this->columnName ) ) {
            $column = __asName( $this->columnName, $sqlType );
        } else {
            $column = $this->columnName . ''; // to string
        }
        return $column . ' BETWEEN ' . parent::toString( $sqlType );
    }

}

// ----------------------------------------------------------------------------
// Comparison Operators
// ----------------------------------------------------------------------------
class InCondition extends ConditionalOp {

    public function __construct( mixed $leftSide, mixed $rightSide ) {
        parent::__construct( 'IN', $leftSide, $rightSide );
    }

    public function toString( SQLType $sqlType = SQLType::NONE ): string {
        $left = $this->convertToString( $this->leftSide, $sqlType );
        $right = $this->convertToString( $this->rightSide, $sqlType );
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
        public Value|Column $content
    ) {
    }

    protected function add( Condition $c ): Condition {
        $this->conditions []= $c;
        return $c;
    }

    public function equalTo( mixed $rightSide ): Condition {
        return $this->add(
            new ConditionalOp( '=', $this->content, $rightSide )
        );
    }

    public function notEqualTo( mixed $rightSide ): Condition {
        return $this->add(
            new ConditionalOp( '<>', $this->content, $rightSide )
        );
    }

    /** Alias for `notEqualTo()` */
    public function differentFrom( mixed $rightSide ): Condition {
        return $this->notEqualTo( $rightSide );
    }

    public function greaterThan( mixed $rightSide ): Condition {
        return $this->add(
            new ConditionalOp( '>', $this->content, $rightSide )
        );
    }

    public function greaterThanOrEqualTo( mixed $rightSide ): Condition {
        return $this->add(
            new ConditionalOp( '>=', $this->content, $rightSide )
        );
    }

    public function lessThan( mixed $rightSide ): Condition {
        return $this->add(
            new ConditionalOp( '<', $this->content, $rightSide )
        );
    }

    public function lessThanOrEqualTo( mixed $rightSide ): Condition {
        return $this->add(
            new ConditionalOp( '<=', $this->content, $rightSide )
        );
    }

    public function like( mixed $rightSide ): Condition {
        return $this->add(
            new ConditionalOp( 'LIKE',  $this->content, $rightSide )
        );
    }

    protected function isParameter( string $value ): bool {

        // $value = is_string( $v ) ? $v : (string) $v->content;

        if ( $value === '?' ) {
            return true;
        }
        // Named parameter
        return strpos( $value, ':' ) === 0 &&
            ctype_alpha( substr( $value, 1, 1 ) ); // second char is alpha
    }

    protected function makeSpecialLike(
        string|ComparableContent|Value|Param $rightSide,
        bool $addToTheBeginning,
        bool $addToTheEnd,
    ): Condition {

        $isComparable = ! is_string( $rightSide );
        $value = $isComparable ? __toValue( $rightSide ) : $rightSide;

        if ( $this->isParameter( $value ) ) {
            return $this->like( $rightSide );
        }

        if ( $addToTheEnd && ! str_ends_with( $value, '%' ) ) {
            if ( $isComparable ) {
                $rightSide->content .= '%';
            } else {
                $rightSide .= '%';
            }
        }

        if ( $addToTheBeginning && ! str_starts_with( $value, '%' ) ) {
            if ( $isComparable ) {
                $rightSide->content = '%' . $rightSide->content;
            } else {
                $rightSide = '%' . $rightSide;
            }
        }

        return $this->like( $rightSide );
    }

    public function startWith( string|ComparableContent|Value|Param $rightSide ): Condition {
        return $this->makeSpecialLike( $rightSide, false, true );
    }

    public function endWith( string|ComparableContent|Value|Param $rightSide ): Condition {
        return $this->makeSpecialLike( $rightSide, true, false );
    }

    public function contain( string|ComparableContent|Value|Param $rightSide ): Condition {
        return $this->makeSpecialLike( $rightSide, true, true );
    }

    public function between( mixed $min, mixed $max ): Condition {
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
            new ConditionalOp( 'IS', $this->content, new Raw( 'NULL' ) )
        );
    }

    public function isNotNull(): Condition {
        return $this->add(
            new ConditionalOp( 'IS', $this->content, new Raw( 'NOT NULL' ) )
        );
    }

    public function isTrue(): Condition {
        return $this->add(
            new ConditionalOp( 'IS', $this->content, new Raw( 'TRUE' ) )
        );
    }

    public function isFalse(): Condition {
        return $this->add(
            new ConditionalOp( 'IS', $this->content, new Raw( 'FALSE' ) )
        );
    }

    public function toString( SQLType $sqlType = SQLType::NONE ): string {

        $content = __parseColumnAndAlias( $this->content, $sqlType );
        if ( empty( $this->conditions ) ) {
            return $content;
        }
        return $content . ' ' . __conditionsToString( $this->conditions, $sqlType );
    }
}


class ComparableWithColumn extends ComparableContent {

    public function __construct( Column $content ) {
        parent::__construct( $content );
    }

    public function as( string $alias ): ComparableWithColumn {
        /** @var Column */
        $column = $this->content;
        if ( stripos( $column->name, ' AS ' ) === false ) {
            $column->name .= ' AS ' . $alias; // It will be parse by toString() later
        }
        return $this;
    }
}

class Column implements DBStringable {

    public function __construct(
        public string $name
    ) {
    }

    public function toString( SQLType $sqlType = SQLType::NONE ): string {
        return __parseColumnAndAlias( $this->name, $sqlType );
    }
}

class Value implements DBStringable {

    public function __construct(
        public mixed $content
    ) {
    }

    public function toString( SQLType $sqlType = SQLType::NONE ): string {
        return __toValue( $this->content, $sqlType );
    }
}


class Raw implements Stringable {

    public function __construct(
        public string $value
    ) {
    }

    public function __toString(): string {
        return $this->value;
    }
}


class Expression implements DBStringable {

    public function __construct(
        public string $name,
        public bool $isFunction = false,
        public string $arg = '',
        public string $alias = ''
    ) {
    }

    public function toString( SQLType $sqlType = SQLType::NONE ): string {

        $alias = '';
        if ( ! empty( $this->alias ) ) {
            $alias = __asName( $this->alias, $sqlType );
            $alias = ' AS ' . $alias;
        }

        $arg = __valueOrName( $this->arg, $sqlType );

        if ( $this->isFunction ) {
            return $this->name . '(' . $arg . ')' . $alias;
        }
        return $this->name . $arg . $alias;
    }
}


function __addQuotesToIdentifiers( $valueOrColumn, SQLType $sqlType ): string {

    if ( str_starts_with( $valueOrColumn, "'" ) && str_ends_with( $valueOrColumn, "'" ) ) {
        return $valueOrColumn; // Keep it as is
    }

    // Idea: replace the names with names plus quotes/backticks

    [ $begin, $end ] = __getQuoteCharacters( $sqlType );
    $regex = '[A-z_][A-z_0-9]*'; // No spaces allowed

    if ( $sqlType === SQLType::NONE ) { // No quotes/backticks expected
        $regex = '/' . $regex . '/';
        $replacement = $begin . '$0' . $end;
        $valueOrColumn = preg_replace( $regex, $replacement, $valueOrColumn );

    } else {
        // Spaces allowed inside quotes/backticks
        $regex = '/(\\' . $begin . '[A-z_][A-z_0-9 ]*' . '\\' . $end . '|' . $regex . ')/';
        $replacement = fn( array $matches ) => $begin . trim( $matches[ 1 ] ?? '', ' `' ) . $end;
        $valueOrColumn = preg_replace_callback( $regex, $replacement, $valueOrColumn );
    }

    return $valueOrColumn;
}


class AggregateFunction implements DBStringable {

    public function __construct(
        public string $functionName,
        public bool $distinct,
        public bool|int|float|string|ComparableContent $valueOrColumn,
        public string $alias = ''
    ) {
    }

    public function as( string $alias ): AggregateFunction {
        $this->alias = $alias;
        return $this;
    }

    public function toString( SQLType $sqlType = SQLType::NONE ): string {

        $valueOrColumn = trim( __valueOrName( $this->valueOrColumn, $sqlType ), ' `' );

        if ( trim( $valueOrColumn ) != '*' ) {
            $valueOrColumn = __addQuotesToIdentifiers( $valueOrColumn, $sqlType );
        }

        $alias = __asName( $this->alias, $sqlType );
        if ( $alias != '' ) {
            $alias = ' AS ' . $alias;
        }

        $dist = $this->distinct ? 'DISTINCT ': '';
        $f = "{$this->functionName}({$dist}{$valueOrColumn})" . $alias;
        return $f;
    }

}


abstract class LazyConversionFunction implements DBStringable {

    protected string $aliasValue = '';

    public function as( string $alias ): LazyConversionFunction {
        $this->aliasValue = $alias;
        return $this;
    }
}


// ----------------------------------------------------------------------------
// SELECT COMMAND
// ----------------------------------------------------------------------------

class Select implements DBStringable, Stringable {

    /** @var mixed[] */
    protected $columns = [];
    protected ?From $from = null;

    public function __construct(
        protected bool $distinct,
        mixed ...$columns
    ) {
        // OLD:
        // if ( empty( $columns ) ) {
        //     $columns = [ '*' ];
        // }
        // $this->columns = array_map( fn($c) => __parseColumnAndAlias( $c ), $columns );

        $this->columns = $columns;
    }

    public function from( string $table, string ...$tables ): From {
        array_unshift( $tables, $table );

        /** @var TableData[] */
        $tableData = array_map( fn( $t ) => new TableData( $t ), $tables );

        $this->from = new From( $this, $tableData );
        return $this->from;
    }


    public function toString( SQLType $sqlType = SQLType::NONE ): string {

        $columns = [];
        if ( empty( $this->columns ) ) {
            $columns = [ '*' ];
        } else {
            $columns = array_map( fn($c) => __parseColumnAndAlias( $c, $sqlType ), $this->columns );
        }
        $from = $this->from ? $this->from->toString( $sqlType ) : '';
        return 'SELECT ' . ( $this->distinct ? 'DISTINCT ' : '' ) . implode( ', ', $columns ) . $from;
    }


    public function __toString(): string {
        return $this->toString( SQL::$type ); // Uses the database type set as default
    }
}


class TableData implements DBStringable {

    public function __construct(
        public readonly string $table
    ) {
    }

    public function toString( SQLType $sqlType = SQLType::NONE ): string {

        $pieces = __parseSeparatedValues( $this->table );
        $tableName = __asName( $pieces[ 0 ] ?? '', $sqlType );
        $tableAlias = __asName( $pieces[ 1 ] ?? '', $sqlType );

        // ---------------------------------------------------------------------

        if ( $tableAlias != '' ) { // It has an alias
            return $tableName . ' ' . $tableAlias;
        }
        return $tableName;
    }
}


class From implements DBStringable {

    use CanLimit;

    protected ?Condition $whereCondition = null;

    protected ?Select $whereExistsSelect = null;

    /** @var Join[] $joins */
    protected $joins = [];

    /** @var string[] $groupByColumns */
    protected $groupByColumns = [];

    protected ?Condition $havingCondition = null;

    /** @var ColumnOrdering[] $columnOrderings */
    protected $columnOrderings = [];
    protected ?Select $unionSelect = null;
    protected bool $isUnionDistinct = false;

    /**
     * Constructor
     *
     * @param \phputil\sql\Select $parent
     * @param TableData[] $tables
     */
    public function __construct(
        protected Select $parent,
        protected $tables
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

    /**
     * A natural join is **not** supported by SQL Server.
     *
     * @param string $table
     * @return Join
     */
    public function naturalJoin( string $table ): Join {
        return $this->makeJoin( $table, 'NATURAL JOIN' );
    }

    public function where( Condition $condition ): self {
        $this->whereCondition = $condition;
        return $this;
    }

    public function whereExists( Select $select ): self {
        $this->whereExistsSelect = $select;
        return $this;
    }

    public function groupBy( string ...$columns ): self {
        $this->groupByColumns = $columns;
        return $this;
    }

    public function having( Condition $condition ): self {
        $this->havingCondition = $condition;
        return $this;
    }

    public function orderBy( string|AggregateFunction|ColumnOrdering ...$columns ): self {
        $toOrdering = fn($c) => is_object( $c ) && $c instanceof ColumnOrdering ? $c : new ColumnOrdering( $c );
        $this->columnOrderings = array_map( $toOrdering, $columns );
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

    public function endAsString( SQLType $sqlType = SQLType::NONE ): string {
        return $this->end()->toString( $sqlType );
    }

    public function toString( SQLType $sqlType = SQLType::NONE ): string {

        $tableNames = array_map( fn( $t ) => $t->toString( $sqlType ), $this->tables );
        $s = ' FROM ' . implode( ', ', $tableNames );
        foreach ( $this->joins as $j ) {
            $s .= ' ' . $j->toString( $sqlType );
        }

        $where = ( $this->whereCondition != null ) ? $this->whereCondition->toString( $sqlType ) : '';
        if ( $where != '' ) {
            $s .= ' WHERE ' . $where;
        }

        $whereExists = ( $this->whereExistsSelect != null ) ? $this->whereExistsSelect->toString( $sqlType ) : '';
        if ( $whereExists != '' ) {
            $s .= ' WHERE EXISTS (' . $whereExists . ')';
        }

        $groupByColumns = array_map( fn($c) => __parseColumnAndAlias( $c, $sqlType ), $this->groupByColumns );

        if ( ! empty( $groupByColumns ) ) {
            $s .= ' GROUP BY ' . implode( ', ', $groupByColumns );

            if ( $this->havingCondition ) {
                $s .= ' HAVING ' . $this->havingCondition->toString( $sqlType );
            }
        }

        if ( ! empty( $this->columnOrderings )) {
            $orderings = array_map( fn( $c ) => $c->toString( $sqlType ), $this->columnOrderings );
            $s .= ' ORDER BY ' . implode( ', ', $orderings );
        }

        $limitOffset = $this->makeLimitAndOffset( $sqlType );
        if ( ! empty( $limitOffset ) ) {
            $s .= $limitOffset;
        }

        if ( $this->unionSelect !== null ) {
            $s .= ' UNION ' . ( $this->isUnionDistinct ? 'DISTINCT ' : '' ) . $this->unionSelect->toString( $sqlType );
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

    public function toString( SQLType $sqlType = SQLType::NONE ): string {

        $values = __parseSeparatedValues( $this->table );
        $declaration = __asName( $values[ 0 ], $sqlType );
        if ( isset( $values[ 1 ] ) ) {
            $declaration .= ' ' . __asName( $values[ 1 ], $sqlType );
        }

        return $this->type . ' ' . $declaration . ' ON ' . $this->condition->toString( $sqlType );
    }
}


class ColumnOrdering implements DBStringable {

    public function __construct(
        protected string|AggregateFunction $column,
        protected bool $isDescending = false
    ) {
    }

    public function toString( SQLType $sqlType = SQLType::NONE ): string {

        $direction = $this->isDescending ? ' DESC' : ' ASC';

        if ( is_object( $this->column ) ) {
            return $this->column->toString( $sqlType ) . $direction;
        }

        $pieces = __parseSeparatedValues( $this->column );
        $column = __parseColumnAndAlias( $pieces[ 0 ] ?? '', $sqlType );
        if ( isset( $pieces[ 1 ] ) ) { // It can have ASC/DESC in the field name
            $direction = strtoupper( $pieces[ 1 ] ?? 'ASC' ) == 'DESC' ? ' DESC' : ' ASC';
        }
        return $column . $direction;
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
    protected function makeLimitAndOffset( SQLType $sqlType = SQLType::NONE ): string {

        // Compatible with: SQLServer, Oracle.

        if ( $sqlType === SQLType::ORACLE || $sqlType === SQLType::SQLSERVER ) {
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


class ConditionWrapper implements Condition {

    public function __construct(
        protected Condition $condition,
        protected string $prefix = ''
    ) {
    }

    public function toString( SQLType $sqlType = SQLType::NONE ): string {
        return $this->prefix . '('. $this->condition->toString( $sqlType ) . ')';
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

/**
 * Get quote characters used by a certain database/sql type.
 *
 * @param \phputil\sql\SQLType $sqlType
 * @return string[]
 */
function __getQuoteCharacters( SQLType $sqlType = SQLType::NONE ): array {
    return match( $sqlType ) {
        SQLType::NONE => [ '', '' ], // Empty
        SQLType::MYSQL, SQLType::SQLITE => [ '`', '`' ], // Backticks
        SQLType::ORACLE, SQLType::POSTGRESQL => [ '"', '"' ], // Quotes
        SQLType::SQLSERVER => [ '[', ']' ], // Square brackets
    };
}

/**
 * Get values that are separated by spaces.
 *
 * @param string $column
 * @return string[]
 */
function __parseSeparatedValues( string $column ): array {
    $pieces = explode( ' ', $column );
    $pieces = array_map( 'trim', $pieces );
    return array_filter( $pieces, fn($v) => $v != '' );
}

/**
 * Converts an array of conditions to a string.
 *
 * @param Condition[] $conditions
 * @param \phputil\sql\SQLType $sqlType
 * @return string
 */
function __conditionsToString( array $conditions, SQLType $sqlType ): string {
    $r = '';
    foreach ( $conditions as $c ) {
        $r .= ' ' . $c->toString( $sqlType );
    }
    return $r;
}


function __parseColumnAndAlias( mixed $column, SQLType $sqlType ): string {

    if ( $column instanceof ComparableContent ) {
        $column = $column->content;
    }

    if ( $column instanceof Value ) {
        $c = $column->content;
        if ( ! is_object( $c ) ) {
            return (string) $c;
        }
        $column = $c; // E.g. Aggregate Function
    }

    if ( $column instanceof Expression ||
        $column instanceof LazyConversionFunction ||
        $column instanceof AggregateFunction ||
        $column instanceof Column
    ) {
        return $column->toString( $sqlType );
    }

    if ( $column instanceof DBStringable ) {
        $column = $column->toString( $sqlType );
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
                $table = __asName( $pieces[ 0 ], $sqlType );
                $column = $table . '.' . __asName( $pieces[ 1 ], $sqlType );
            } else {
                $column = __asName( $pieces[ 0 ], $sqlType );
            }
        }
        if ( isset( $matches[ 2 ] ) ) {
            $alias = __asName( trim( $matches[ 2 ] ), $sqlType );
            return $column . ' AS ' . $alias;
        }
    }
    return $column;
}

function __asName( string $name, SQLType $sqlType ): string {
    if ( $name === '*' ) { // Ignore quotes for a star
        return $name;
    }
    $quotes = __getQuoteCharacters( $sqlType );
    if ( $quotes[ 0 ] != '' && $name != '' && $name[ 0 ] != $quotes[ 0 ] ) {
        return $quotes[ 0 ] . $name . $quotes[ 1 ];
    }
    return $name;
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

function __toValue(
    mixed $value,
    SQLType $sqlType = SQLType::NONE
): string {

    if ( is_null( $value ) ) {
        return 'NULL';
    } else if ( is_string( $value ) ) {
        return __toString( $value );
    } else if ( $value instanceof ComparableContent || $value instanceof Value ) {
        return __toValue( $value->content, $sqlType );
    } else if ( $value instanceof Value ) {
        $content = $value->content;
        if ( is_string( $content ) || is_numeric( $content ) ) {
            return (string) $content;
        }
        return __toValue( $content, $sqlType );
    } else if ( $value instanceof Column ) {
        return __asName( $value->toString( $sqlType ), $sqlType );
    } else if ( $value instanceof DBStringable ||
        $value instanceof LazyConversionFunction
    ) {
        return $value->toString( $sqlType );
    } else if ( $value instanceof DateTimeInterface ) {
        return __toDateString( $value );
    } else if ( is_bool( $value ) ) {
        return __toBoolean( $value, $sqlType === SQLType::ORACLE );
    } else if ( is_array( $value ) ) {
        $toValue = fn($v) => __toValue( $v, $sqlType );
        return implode( ', ', array_map( $toValue, $value ) );
    }

    return "$value"; // to string
}

function __valueOrName( mixed $str, SQLType $sqlType ): string {
    if ( $str instanceof LazyConversionFunction ) {
        $str = $str->toString( $sqlType );
    } else if ( $str instanceof Value ) {
        $str = __toValue( $str->content, $sqlType );
    } else if ( is_string( $str ) ) {
        $str = __asName( $str, $sqlType );
    } else if ( $str instanceof ComparableWithColumn ) {
        return __valueOrName( $str->content, $sqlType );
    } else if ( $str instanceof Column ) {
        $str = __asName( $str->name, $sqlType );
    } else if ( $str instanceof ComparableContent ) {
        // $str = $str->toString( $sqlType );
        $str = __valueOrName( $str->content, $sqlType ); // Evaluates according to the content type
        // $str = __toValue( $str->content, $sqlType );
    }
    return "$str";
}
// ----------------------------------------------------------------------------
// BASIC FUNCTIONS
// ----------------------------------------------------------------------------

function select( mixed ...$columns ): Select {
    return new Select( false, ...$columns );
}

function selectDistinct( mixed ...$columns ): Select {
    return new Select( true, ...$columns );
}

function col( string $name ): ComparableWithColumn {
    return new ComparableWithColumn( new Column( $name ) );
}

function val( mixed $value ): ComparableContent {
    $v = null;
    if ( $value instanceof DateTimeInterface ) {
        $v = new Value( __toDateString( $value ) );
    } else {
        $v = new Value( $value );
    }
    return new ComparableContent( $v );
}

class Param implements Stringable {

    public function __construct(
        public string $value
    ) {
    }

    public function __toString(): string {
        return $this->value;
    }
}

function param( string $value = '?' ): Param {
    $value = trim( $value );
    if ( $value === '' || $value === ':' ) {
        $value = '?';
    } else if ( $value != '?' && ! str_starts_with( $value, ':' ) ) {
        $value = ':' . $value;
    }
    // return val( $value );
    return new Param( $value );
}

function quote( string $value ): string {
    return __toString( $value );
}

function wrap( Condition $c ): Condition {
    return new ConditionWrapper( $c );
}

function not( Condition $c ): Condition {
    return new ConditionWrapper( $c, 'NOT ' );
}

// ----------------------------------------------------------------------------
// UTILITIES
// ----------------------------------------------------------------------------

function desc( string|AggregateFunction $column ): ColumnOrdering {
    // return $column . ' DESC';
    return new ColumnOrdering( $column, true );
}

function asc( string|AggregateFunction $column ): ColumnOrdering {
    // return $column . ' ASC';
    return new ColumnOrdering( $column, false );
}

function andAll( Condition $first, Condition ...$others ): Condition {
    return __joinConditions( true, ...[ $first, ...$others ] );
}

function orAll( Condition $first, Condition ...$others ): Condition {
    return __joinConditions( false, ...[ $first, ...$others ] );
}

function __joinConditions(
    bool $isAnd,
    Condition $first,
    Condition ...$others
): Condition {
    $new = $first;
    $max = \count( $others );
    $i = 0;
    while ( $i < $max ) {
        if ( $isAnd ) {
            $new = $new->and( $others[ $i ] );
        } else {
            $new = $new->or( $others[ $i ] );
        }
        $i++;
    }
    return $new;
}

// ----------------------------------------------------------------------------
// AGGREGATE FUNCTIONS
// ----------------------------------------------------------------------------

function count( string|ComparableContent $column, string $alias = '' ): AggregateFunction {
    return new AggregateFunction( 'COUNT', false, $column, $alias );
}

function countDistinct( string|ComparableContent $column, string $alias = '' ): AggregateFunction {
    return new AggregateFunction( 'COUNT', true, $column, $alias );
}

function sum( string|ComparableContent $column, string $alias = '' ): AggregateFunction {
    return new AggregateFunction( 'SUM', false, $column, $alias );
}

function sumDistinct( string|ComparableContent $column, string $alias = '' ): AggregateFunction {
    return new AggregateFunction( 'SUM', true, $column, $alias );
}

function avg( string|ComparableContent $column, string $alias = '' ): AggregateFunction {
    return new AggregateFunction( 'AVG', false, $column, $alias );
}

function avgDistinct( string|ComparableContent $column, string $alias = '' ): AggregateFunction {
    return new AggregateFunction( 'AVG', true, $column, $alias );
}

function min( string|ComparableContent $column, string $alias = '' ): AggregateFunction {
    return new AggregateFunction( 'MIN', false, $column, $alias );
}

function max( string|ComparableContent $column, string $alias = '' ): AggregateFunction {
    return new AggregateFunction( 'MAX', false, $column, $alias );
}

// ----------------------------------------------------------------------------
// DATE AND TIME FUNCTIONS
// ----------------------------------------------------------------------------

function now(): LazyConversionFunction {
    return new class extends LazyConversionFunction {

        public function toString( SQLType $sqlType = SQLType::NONE ): string {
            $e = match ( $sqlType ) {
                SQLType::SQLITE => new Expression( 'DATETIME', true, "'now'", $this->aliasValue ),
                SQLType::ORACLE => new Expression( 'SYSDATE', false, '', $this->aliasValue  ),
                SQLType::SQLSERVER => new Expression( 'CURRENT_TIMESTAMP', false, '', $this->aliasValue  ),
                default => new Expression( 'NOW', true, '', $this->aliasValue ) // MySQL, PostgreSQL
            };
            return $e->toString( $sqlType );
        }

    };
}


function date(): LazyConversionFunction {
    return new class extends LazyConversionFunction {

        public function toString( SQLType $sqlType = SQLType::NONE ): string {
            $e = match ( $sqlType ) {
                SQLType::ORACLE => new Expression( 'SYSDATE', false ),
                SQLType::SQLSERVER => new Expression( 'GETDATE', true ),
                default => new Expression( 'CURRENT_DATE', false ) // MySQL, PostgreSQL, SQLite
            };
            return $e->toString( $sqlType );
        }

    };
}


function time(): LazyConversionFunction {
    return new class extends LazyConversionFunction {

        public function toString( SQLType $sqlType = SQLType::NONE ): string {
            $e = match ( $sqlType ) {
                SQLType::ORACLE, SQLType::SQLSERVER => new Expression( 'CURRENT_TIMESTAMP', false ),
                default => new Expression( 'CURRENT_TIME', false ) // MySQL, PostgreSQL, SQLite
            };
            return $e->toString( $sqlType );
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

class ExtractFunction extends LazyConversionFunction {

    protected string|ComparableContent|LazyConversionFunction $dateOrColumn;

    public function __construct( protected Extract $unit ) {}


    public function from( string|ComparableContent|LazyConversionFunction $dateOrColumn ): ExtractFunction {
        $this->dateOrColumn = $dateOrColumn;
        return $this;
    }

    public function toString( SQLType $sqlType = SQLType::NONE ): string {

        $unit = $this->unit->name;
        if ( $sqlType === SQLType::SQLITE ) {
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

        $date = __valueOrName( $this->dateOrColumn, $sqlType );
        return match ( $sqlType ) {
            SQLType::SQLSERVER => ( new Expression( 'DATEPART', true, "$unit, $date" ) )->toString( $sqlType ),
            SQLType::SQLITE => "strftime('%{$unit}', $date)",
            default => "EXTRACT($unit FROM $date)" // MySQL, PostgreSQL, Oracle
        };
    }
}


function extract(
    Extract $unit,
    string|ComparableContent|LazyConversionFunction $dateOrColumn = ''
): ExtractFunction {
    if ( is_string( $dateOrColumn ) && empty( $dateOrColumn ) ) {
        return ( new ExtractFunction( $unit ) )->from( val( '' ) );
    }
    return ( new ExtractFunction( $unit ) )->from( $dateOrColumn );
}

function diffInDays(
    string|ComparableContent|LazyConversionFunction $startDate,
    string|ComparableContent|LazyConversionFunction $endDate
): LazyConversionFunction {

    return new class ( $startDate, $endDate ) extends LazyConversionFunction {

        public function __construct(
            protected string|ComparableContent|LazyConversionFunction $startDate,
            protected string|ComparableContent|LazyConversionFunction $endDate
            ) {}

        public function toString( SQLType $sqlType = SQLType::NONE ): string {
            $startDate = __valueOrName( $this->startDate, $sqlType );
            $endDate = __valueOrName( $this->endDate, $sqlType );
            return match ( $sqlType ) {
                SQLType::ORACLE, SQLType::POSTGRESQL, SQLType::SQLITE => "$endDate - $startDate",
                SQLType::SQLSERVER => "DATEDIFF(day, $startDate, $endDate)",
                default => "DATEDIFF($startDate, $endDate)" // MySQL
            };
        }
    };
}

function addDays(
    string|ComparableContent|LazyConversionFunction $dateOrColumn,
    int|string|LazyConversionFunction $value
): LazyConversionFunction {

    return new class ( $dateOrColumn, $value ) extends LazyConversionFunction {

        public function __construct(
            protected string|ComparableContent|LazyConversionFunction $dateOrColumn,
            protected int|string|LazyConversionFunction $value
            ) {}

        public function toString( SQLType $sqlType = SQLType::NONE ): string {
            $unit = match ( $sqlType ) {
                SQLType::POSTGRESQL => 'days',
                default => 'day'
            };
            return dateAdd( $this->dateOrColumn, $this->value, $unit )->toString( $sqlType );
        }
    };
}

function subDays(
    string|ComparableContent|LazyConversionFunction $dateOrColumn,
    int|string|LazyConversionFunction $value
): LazyConversionFunction {

    return new class ( $dateOrColumn, $value ) extends LazyConversionFunction {

        public function __construct(
            protected string|ComparableContent|LazyConversionFunction $dateOrColumn,
            protected int|string|LazyConversionFunction $value
            ) {}

        public function toString( SQLType $sqlType = SQLType::NONE ): string {
            $unit = match ( $sqlType ) {
                SQLType::POSTGRESQL => 'days',
                default => 'day'
            };
            return dateSub( $this->dateOrColumn, $this->value, $unit )->toString( $sqlType );
        }
    };
}

function dateAdd(
    string|ComparableContent|LazyConversionFunction $dateOrColumn,
    int|string|ComparableContent|LazyConversionFunction $value,
    string $unit = 'day'
): LazyConversionFunction {

    return new class ( $dateOrColumn, $value, $unit ) extends LazyConversionFunction {

        public function __construct(
            protected string|ComparableContent|LazyConversionFunction $dateOrColumn,
            protected int|string|ComparableContent|LazyConversionFunction $value,
            protected string $unit
            ) {}

        public function toString( SQLType $sqlType = SQLType::NONE ): string {
            $dateOrColumn = __valueOrName( $this->dateOrColumn, $sqlType );
            $value = __toValue( $this->value, $sqlType );
            $unit = $sqlType === SQLType::MYSQL ? strtoupper( $this->unit ) : strtolower( $this->unit );
            return match ( $sqlType ) {
                SQLType::ORACLE => "$dateOrColumn + $value",
                SQLType::SQLSERVER => "DATEADD($unit, $value, $dateOrColumn)",
                SQLType::SQLITE => "DATE($dateOrColumn, +{$value} $unit",
                SQLType::POSTGRESQL => "$dateOrColumn + INTERVAL $value $unit",
                default => "DATE_ADD($dateOrColumn, INTERVAL $value $unit)" // MySQL
            };
        }
    };
}

function dateSub(
    string|ComparableContent|LazyConversionFunction $dateOrColumn,
    int|string|ComparableContent|LazyConversionFunction $value,
    string $unit = 'day'
): LazyConversionFunction {

    return new class ( $dateOrColumn, $value, $unit ) extends LazyConversionFunction {

        public function __construct(
            protected string|ComparableContent|LazyConversionFunction $dateOrColumn,
            protected int|string|ComparableContent|LazyConversionFunction $value,
            protected string $unit
            ) {}

        public function toString( SQLType $sqlType = SQLType::NONE ): string {
            $dateOrColumn = __valueOrName( $this->dateOrColumn, $sqlType );
            $value = __toValue( $this->value, $sqlType );
            $unit = $sqlType === SQLType::MYSQL ? strtoupper( $this->unit ) : strtolower( $this->unit );
            return match ( $sqlType ) {
                SQLType::ORACLE => "$dateOrColumn - $value",
                SQLType::SQLSERVER => "DATESUB($unit, $value, $dateOrColumn)",
                SQLType::SQLITE => "DATE($dateOrColumn, -{$value} $unit",
                SQLType::POSTGRESQL => "$dateOrColumn - INTERVAL $value $unit",
                default => "DATE_SUB($dateOrColumn, INTERVAL $value $unit)" // MySQL
            };
        }
    };
}

// ----------------------------------------------------------------------------
// STRING FUNCTIONS
// ----------------------------------------------------------------------------

function upper( string|ComparableContent $textOrColumn ): LazyConversionFunction {
    return new class ( $textOrColumn ) extends LazyConversionFunction {

        public function __construct( protected string|ComparableContent $textOrColumn ) {}

        public function toString( SQLType $sqlType = SQLType::NONE ): string {
            $textOrColumn = __valueOrName( $this->textOrColumn, $sqlType );
            return "UPPER($textOrColumn)";
        }
    };
}

function lower( string|ComparableContent $textOrColumn ): LazyConversionFunction {
    return new class ( $textOrColumn ) extends LazyConversionFunction {

        public function __construct( protected string|ComparableContent $textOrColumn ) {}

        public function toString( SQLType $sqlType = SQLType::NONE ): string {
            $textOrColumn = __valueOrName( $this->textOrColumn, $sqlType );
            return "LOWER($textOrColumn)";
        }
    };
}

function substring(
    string|ComparableContent $textOrColumn,
    int|string $pos = 1,
    int $len = 0
    ): LazyConversionFunction {

    return new class ( $textOrColumn, $pos, $len ) extends LazyConversionFunction {

        public function __construct(
            protected string|ComparableContent $textOrColumn,
            protected int|string $pos = 1,
            protected int $len = 0
            ) {}

        public function toString( SQLType $sqlType = SQLType::NONE ): string {
            $textOrColumn = __valueOrName( $this->textOrColumn, $sqlType );
            $pos = $this->pos;
            $len = $this->len;
            return match ( $sqlType ) {
                SQLType::POSTGRESQL => ( $len > 0 ? "SUBSTRING($textOrColumn FROM $pos FOR $len)" : "SUBSTRING($textOrColumn FROM $pos)" ),
                SQLType::SQLITE, SQLType::ORACLE => ( $len > 0 ? "SUBSTR($textOrColumn, $pos, $len)" : "SUBSTR($textOrColumn, $pos)" ),
                SQLType::SQLSERVER => "SUBSTRING($textOrColumn, $pos, $len)",
                default => ( $len > 0 ? "SUBSTRING($textOrColumn, $pos, $len)" : "SUBSTRING($textOrColumn, $pos)" ) // MySQL
            };
        }
    };
}


function concat(
    string|ComparableContent $textOrColumn1,
    string|ComparableContent $textOrColumn2,
    string|ComparableContent ...$other
    ): LazyConversionFunction {

    return new class (  $textOrColumn1, $textOrColumn2, ...$other ) extends LazyConversionFunction {

        /** @var string[]|\phputil\sql\ComparableContent[] */
        protected $other;

        public function __construct(
            protected string|ComparableContent $textOrColumn1,
            protected string|ComparableContent $textOrColumn2,
            string|ComparableContent ...$other
            ) {
            $this->other = $other;
        }

        public function toString( SQLType $sqlType = SQLType::NONE ): string {
            $textOrColumn1 = __valueOrName( $this->textOrColumn1, $sqlType );
            $textOrColumn2 = __valueOrName( $this->textOrColumn2, $sqlType );
            $other = array_map( fn( $s ) => __valueOrName( $s, $sqlType ), $this->other );

            if ( $sqlType === SQLType::ORACLE ) {
                return implode( ' || ', [ $textOrColumn1, $textOrColumn2, ...$other ] );
            }

            $params = implode( ', ', [ $textOrColumn1, $textOrColumn2, ...$other ] );
            return "CONCAT($params)";
        }
    };
}


function length( string|ComparableContent $textOrColumn ): LazyConversionFunction {
    return new class ( $textOrColumn ) extends LazyConversionFunction {

        public function __construct(
            protected string|ComparableContent $textOrColumn
            ) {}

        public function toString( SQLType $sqlType = SQLType::NONE ): string {
            $textOrColumn = __valueOrName( $this->textOrColumn, $sqlType );
            return match ( $sqlType ) {
                SQLType::SQLSERVER => "LEN($textOrColumn)",
                SQLType::MYSQL, SQLType::POSTGRESQL => "CHAR_LENGTH($textOrColumn)",
                default => "LENGTH($textOrColumn)"
            };
        }
    };
}


function bytes( string|ComparableContent $textOrColumn ): LazyConversionFunction {
    return new class ( $textOrColumn ) extends LazyConversionFunction {

        public function __construct(
            protected string|ComparableContent $textOrColumn
            ) {}

        public function toString( SQLType $sqlType = SQLType::NONE ): string {
            $textOrColumn = __valueOrName( $this->textOrColumn, $sqlType );
            return match ( $sqlType ) {
                SQLType::SQLSERVER => "LEN($textOrColumn)",
                default => "LENGTH($textOrColumn)"
            };
        }
    };
}

// ----------------------------------------------------------------------------
// NULL HANDLING FUNCTIONS
// ----------------------------------------------------------------------------

function ifNull(
    string|ComparableContent $valueOrColumm,
    string|int|float|bool|ComparableContent $valueOrColumnIfNull
    ): LazyConversionFunction {
    return new class ( $valueOrColumm, $valueOrColumnIfNull ) extends LazyConversionFunction {

        public function __construct(
            protected string|ComparableContent $valueOrColumm,
            protected string|int|float|bool|ComparableContent $valueOrColumnIfNull
            ) {}

        public function toString( SQLType $sqlType = SQLType::NONE ): string {
            $valueOrColumm = __valueOrName( $this->valueOrColumm, $sqlType );
            $valueOrColumnIfNull = __valueOrName( $this->valueOrColumnIfNull, $sqlType );
            return match ( $sqlType ) {
                SQLType::POSTGRESQL, SQLType::MYSQL => "COALESCE($valueOrColumm, $valueOrColumnIfNull)",
                SQLType::ORACLE => "NVL($valueOrColumm,$valueOrColumnIfNull)",
                default => "IFNULL($valueOrColumm, $valueOrColumnIfNull)"
            };
        }
    };
}

// ----------------------------------------------------------------------------
// MATHEMATICAL FUNCTIONS
// ----------------------------------------------------------------------------

class ValueOrColumnBasedOnDemandFunction extends LazyConversionFunction {
    public function __construct(
        protected string $functionName,
        protected string|int|float|ComparableContent $valueOrColumn
        ) {}

    public function toString( SQLType $sqlType = SQLType::NONE ): string {
        $valueOrColumn = __valueOrName( $this->valueOrColumn, $sqlType );
        return $this->functionName . '(' . $valueOrColumn . ')';
    }
}


function abs( string|int|float|ComparableContent $valueOrColumn ): LazyConversionFunction {
    return new ValueOrColumnBasedOnDemandFunction( 'ABS', $valueOrColumn );
}

function round( string|int|float|ComparableContent $valueOrColumn, int $decimals = 2 ): LazyConversionFunction {
    return new class ( $valueOrColumn, $decimals ) extends LazyConversionFunction {

        public function __construct(
            protected string|int|float|ComparableContent $valueOrColumn,
            protected int $decimals = 2
            ) {}

        public function toString( SQLType $sqlType = SQLType::NONE ): string {
            $valueOrColumn = __valueOrName( $this->valueOrColumn, $sqlType );
            $decimals = $this->decimals;
            return "ROUND($valueOrColumn, $decimals)";
        }
    };
}

function ceil( string|int|float|ComparableContent $valueOrColumn ): LazyConversionFunction {
    return new class ( $valueOrColumn ) extends LazyConversionFunction {

        public function __construct(
            protected string|int|float|ComparableContent $valueOrColumn
            ) {}

        public function toString( SQLType $sqlType = SQLType::NONE ): string {
            $valueOrColumn = __valueOrName( $this->valueOrColumn, $sqlType );
            return match( $sqlType ) {
                SQLType::SQLSERVER => "CEILING($valueOrColumn)",
                default => "CEIL($valueOrColumn)"
            };
        }
    };
}

function floor( string|int|float|ComparableContent $valueOrColumn ): LazyConversionFunction {
    return new ValueOrColumnBasedOnDemandFunction( 'FLOOR', $valueOrColumn );
}

function power(
    string|int|float|ComparableContent $base,
    string|int|float|ComparableContent $exponent
    ): LazyConversionFunction {
    return new class ( $base, $exponent ) extends LazyConversionFunction {

        public function __construct(
            protected string|int|float|ComparableContent $base,
            protected string|int|float|ComparableContent $exponent
            ) {}

        public function toString( SQLType $sqlType = SQLType::NONE ): string {
            $base = __valueOrName( $this->base, $sqlType );
            $exponent = __valueOrName( $this->exponent, $sqlType );
            return "POWER($base, $exponent)";
        }
    };
}

function sqrt( string|int|float|ComparableContent $valueOrColumn ): LazyConversionFunction {
    return new ValueOrColumnBasedOnDemandFunction( 'SQRT', $valueOrColumn );
}

function sin( string|int|float|ComparableContent $valueOrColumn ): LazyConversionFunction {
    return new ValueOrColumnBasedOnDemandFunction( 'SIN', $valueOrColumn );
}

function cos( string|int|float|ComparableContent $valueOrColumn ): LazyConversionFunction {
    return new ValueOrColumnBasedOnDemandFunction( 'COS', $valueOrColumn );
}

function tan( string|int|float|ComparableContent $valueOrColumn ): LazyConversionFunction {
    return new ValueOrColumnBasedOnDemandFunction( 'TAN', $valueOrColumn );
}

// ----------------------------------------------------------------------------
// DELETE
// ----------------------------------------------------------------------------

class DeleteCommand implements DBStringable, Stringable {

    protected ?Condition $whereCondition = null;

    public function __construct(
        protected string $table
    ) {
    }

    public function where( Condition $condition ): self {
        $this->whereCondition = $condition;
        return $this;
    }

    public function end(): self {
        return $this;
    }

    public function endAsString( SQLType $sqlType = SQLType::NONE ): string {
        return $this->toString( $sqlType );
    }

    public function toString( SQLType $sqlType = SQLType::NONE ): string {

        $s = 'DELETE FROM ' . __asName( $this->table, $sqlType );
        if ( $this->whereCondition !== null ) {
            $s .= ' WHERE ' . $this->whereCondition->toString( $sqlType );
        }
        return $s;
    }


    public function __toString(): string {
        return $this->toString( SQL::$type ); // Uses the database type set as default
    }
}


function deleteFrom( string $table ): DeleteCommand {
    return new DeleteCommand( $table );
}



// ----------------------------------------------------------------------------
// INSERT
// ----------------------------------------------------------------------------

class InsertCommand implements DBStringable, Stringable {

    protected $valuesMatrix = [];

    /**
     * Creates the insert command
     *
     * @param string $table
     * @param string[]|ComparableWithColumn[] $columns
     */
    public function __construct(
        protected string $table,
        protected $columns = [],
        protected ?Select $selectClause = null
    ) {
    }

    public function values( array $first, array ...$others ): self {
        $this->valuesMatrix = [ $first, ...$others ];
        return $this;
    }

    public function end(): self {
        return $this;
    }

    public function endAsString( SQLType $sqlType = SQLType::NONE ): string {
        return $this->toString( $sqlType );
    }

    public function toString( SQLType $sqlType = SQLType::NONE ): string {

        $s = 'INSERT INTO ' . __asName( $this->table, $sqlType );

        // COLUMNS
        if ( array_key_exists( 0, $this->columns ) ) {
            $c = implode( ', ',
                array_map( fn( $col ) => __asName( $col, $sqlType ), $this->columns )
            );
            $s .= ' (' . $c . ')';
        }

        // VALUES
        if ( array_key_exists( 0, $this->valuesMatrix ) ) {
            $v = '';
            if ( is_array( $this->valuesMatrix[ 0 ] ) ) { // Matrix
                $valueRows = [];
                foreach ( $this->valuesMatrix as $value ) {
                    $valueRows []= $this->toValueRow( $value, $sqlType );
                }
                $v = implode( ', ', $valueRows );
            } else {
                $v = $this->toValueRow( $this->valuesMatrix, $sqlType );
            }
            $s .= ' VALUES ' . $v;
        }

        // SELECT
        if ( $this->selectClause !== null ) {
            $s .= ' ' . $this->selectClause->toString( $sqlType );
        }

        return $s;
    }


    public function __toString(): string {
        return $this->toString( SQL::$type ); // Uses the database type set as default
    }

    protected function toValueRow( array $row, SQLType $sqlType ): string {
        $values = array_map( fn( $v ) => __toValue( $v, $sqlType ), $row );
        return '(' . implode( ', ', $values ) . ')';
    }
}


/**
 * Creates an `INSERT` command.
 *
 * @param string $table
 * @param string[]|ComparableWithColumn[] $columns
 * @param ?Select $select
 * @return InsertCommand
 */
function insertInto( string $table, array $columns = [], ?Select $select = null ): InsertCommand {
    return new InsertCommand( $table, $columns, $select );
}


// ----------------------------------------------------------------------------
// UPDATE
// ----------------------------------------------------------------------------

class UpdateCommand implements DBStringable, Stringable {

    protected ?Condition $whereCondition = null;

    /** @var array<string, bool|int|float|string> */
    protected $attributions = [];

    public function __construct(
        protected string $table
    ) {
    }


    /**
     * Set fields, e.g. [ 'field1' => 'value1', 'field2' => 'value2' ]
     *
     * @param array<string, bool|int|float|string> $attributions
     * @return UpdateCommand
     */
    public function set( array $attributions ): self {
        $this->attributions = $attributions;
        return $this;
    }

    public function where( Condition $condition ): self {
        $this->whereCondition = $condition;
        return $this;
    }

    public function end(): self {
        return $this;
    }

    public function endAsString( SQLType $sqlType = SQLType::NONE ): string {
        return $this->toString( $sqlType );
    }

    public function toString( SQLType $sqlType = SQLType::NONE ): string {

        $s = 'UPDATE ' . __asName( $this->table, $sqlType );

        if ( \count( $this->attributions ) > 0 ) {
            $a = [];
            foreach( $this->attributions as $name => $valueOrColumn ) {

                if ( $valueOrColumn instanceof ComparableContent || $valueOrColumn instanceof Param ) {
                    $valueOrColumn = __toValue( $valueOrColumn, $sqlType );
                } else {
                    $valueOrColumn = trim( __valueOrName( $valueOrColumn, $sqlType ), ' `' );
                    $valueOrColumn = __addQuotesToIdentifiers( $valueOrColumn, $sqlType );
                }

                $a []= __asName( $name, $sqlType ) . ' = ' . $valueOrColumn;
            }
            $s .= ' SET ' . implode( ', ', $a );
        }

        if ( $this->whereCondition !== null ) {
            $s .= ' WHERE ' . $this->whereCondition->toString( $sqlType );
        }
        return $s;
    }


    public function __toString(): string {
        return $this->toString( SQL::$type ); // Uses the database type set as default
    }
}


function update( string $table ): UpdateCommand {
    return new UpdateCommand( $table );
}