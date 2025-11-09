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
        string|ComparableContent|Value|CommandParam $rightSide,
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

    public function startWith( string|ComparableContent|Value|CommandParam $rightSide ): Condition {
        return $this->makeSpecialLike( $rightSide, false, true );
    }

    public function endWith( string|ComparableContent|Value|CommandParam $rightSide ): Condition {
        return $this->makeSpecialLike( $rightSide, true, false );
    }

    public function contain( string|ComparableContent|Value|CommandParam $rightSide ): Condition {
        return $this->makeSpecialLike( $rightSide, true, true );
    }

    public function between( mixed $min, mixed $max ): Condition {
        return $this->add(
            new BetweenCondition( $this->content, $min, $max )
        );
    }

    /**
     * The value must be included in a query or array of values.
     * @param From|string[]|int[]|float[] $selection
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

// ----------------------------------------------------------------------------
// Functions and expressions
// ----------------------------------------------------------------------------

function __parseExpression(
    int|float|string|AliasableExpression|ComparableContent $valueOrColumn,
    SQLType $sqlType = SQLType::NONE
): string {

    if ( $valueOrColumn instanceof AliasableExpression ) {
        return $valueOrColumn->toString( $sqlType );
    }

    $valueOrColumn = trim( __valueOrName( $valueOrColumn, $sqlType ), ' `' );

    if ( $valueOrColumn != '*' ) {
        $valueOrColumn = __addQuotesToIdentifiers( $valueOrColumn, $sqlType );
    }
    return $valueOrColumn;
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


abstract class AliasableExpression implements DBStringable {

    protected string $aliasValue = '';

    public function as( string $alias ): self {
        $this->aliasValue = $alias;
        return $this;
    }

    protected function makeAlias( SQLType $sqlType ): string {
        if ( $this->aliasValue === '' ) {
            return '';
        }
        return ' AS ' . __asName( $this->aliasValue, $sqlType );
    }
}


class BasicExpression extends AliasableExpression {

    public function __construct(
        protected string $name,
        protected int|float|string|ComparableContent|AliasableExpression $argument,
        protected bool $isFunction = false,
        string $alias = ''
    ) {
        $this->aliasValue = $alias;
    }

    protected function getArgument( SQLType $sqlType ): string {
        return __parseExpression( $this->argument, $sqlType );
    }

    public function toString( SQLType $sqlType = SQLType::NONE ): string {
        $arg = $this->getArgument( $sqlType );
        if ( ! $this->isFunction ) {
            return $this->name . $arg . $this->makeAlias( $sqlType );
        }
        return $this->name . '(' . $arg . ')' . $this->makeAlias( $sqlType );
    }
}


class AggregateFunction extends BasicExpression {

    protected bool $distinct = false;

    public function __construct(
        string $functionName,
        bool|int|float|string|ComparableContent|AliasableExpression $valueOrColumn,
        string $alias = '',
        bool $distinct = false,
    ) {
        parent::__construct( $functionName, $valueOrColumn, true, $alias );
        $this->distinct = $distinct;
    }

    public function toString( SQLType $sqlType = SQLType::NONE ): string {
        $valueOrColumn = __parseExpression( $this->argument, $sqlType );
        $dist = $this->distinct ? 'DISTINCT ': '';
        $f = "{$this->name}({$dist}{$valueOrColumn})" . $this->makeAlias( $sqlType );
        return $f;
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

        if ( $tableAlias != '' ) { // It has an alias
            return $tableName . ' ' . $tableAlias;
        }
        return $tableName;
    }
}


class From implements DBStringable, Stringable {

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

    /** @var array<int, array{Select, bool}> */
    protected $unions = [];
    protected ?Select $unionSelect = null;
    protected bool $isUnionDistinct = false;

    /**
     * Constructor
     *
     * @param Select $parent
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

    /** Same as a INNER JOIN */
    public function join( string $table ): Join {
        return $this->makeJoin( $table, 'JOIN' );
    }

    public function innerJoin( string $table ): Join {
        return $this->makeJoin( $table, 'INNER JOIN' );
    }

    /** Same as a LEFT OUTER JOIN */
    public function leftJoin( string $table ): Join {
        return $this->makeJoin( $table, 'LEFT JOIN' );
    }

    /** Same as a RIGHT OUTER JOIN */
    public function rightJoin( string $table ): Join {
        return $this->makeJoin( $table, 'RIGHT JOIN' );
    }

    /**
     * A full join is **not** supported by MySQL and SQLite.
     * Please avoid using it if you plan to migrate your queries.
     *
     * @param string $table
     * @return Join
     */
    public function fullJoin( string $table ): Join {
        return $this->makeJoin( $table, 'FULL JOIN' );
    }

    public function crossJoin( string $table ): From {
        return $this->makeJoin( $table, 'CROSS JOIN' )->end();
    }

    /**
     * A natural join is **not** supported by SQL Server.
     * Please avoid using it if you plan to migrate your queries.
     *
     * @param string $table
     * @return From
     */
    public function naturalJoin( string $table ): From {
        return $this->makeJoin( $table, 'NATURAL JOIN' )->end();
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

    protected function makeUnion( Select $unionSelect, bool $isUnionDistinct ): self {
        $this->unions []= [ $unionSelect, $isUnionDistinct ];
        return $this;
    }

    protected function makeUnionString( Select $unionSelect, bool $isUnionDistinct, SQLType $sqlType ): string {
        return ' UNION ' . ( $isUnionDistinct ? 'DISTINCT ' : '' ) . $unionSelect->toString( $sqlType );
    }

    public function union( Select $select ): self {
        return $this->makeUnion( $select, false );
    }

    public function unionDistinct( Select $select ): self {
        return $this->makeUnion( $select, true );
    }

    public function end(): Select {
        return $this->parent;
    }

    public function endAsString( SQLType $sqlType = SQLType::NONE ): string {
        return $this->end()->toString( $sqlType );
    }

    public function __toString(): string {
        return (string) $this->end(); // Simulates the conversion of the entire query
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

        if (  ! empty( $this->unions ) ) {
            foreach ( $this->unions as [ $unionSelect, $isUnionDistinct ] ) {
                $s .= $this->makeUnionString( $unionSelect, $isUnionDistinct, $sqlType );
            }
        }

        return $s;
    }
}




class Join implements DBStringable {

    protected ?Condition $condition = null;

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

    public function end(): From {
        return $this->parent;
    }

    public function toString( SQLType $sqlType = SQLType::NONE ): string {

        $values = __parseSeparatedValues( $this->table );
        $declaration = __asName( $values[ 0 ], $sqlType );
        if ( isset( $values[ 1 ] ) ) {
            $declaration .= ' ' . __asName( $values[ 1 ], $sqlType );
        }

        $r = $this->type . ' ' . $declaration;
        if ( $this->condition !== null ) {
            $r .= ' ON ' . $this->condition->toString( $sqlType );
        }
        return $r;
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
 * @param SQLType $sqlType
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
 * @param SQLType $sqlType
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

    if ( $column instanceof AliasableExpression ||
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
    // $value = trim( $value );
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
        $value instanceof AliasableExpression
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
    if ( $str instanceof AliasableExpression ) {
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

