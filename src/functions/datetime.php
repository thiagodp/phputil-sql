<?php
namespace phputil\sql;

require_once __DIR__ . '/../internal.php';

// ----------------------------------------------------------------------------
// DATE AND TIME HANDLING FUNCTIONS
// ----------------------------------------------------------------------------

function now(): AliasableExpression {
    return new class extends AliasableExpression {

        public function toString( SQLType $sqlType = SQLType::NONE ): string {
            $e = match ( $sqlType ) {
                SQLType::SQLITE => new BasicExpression( 'DATETIME', "'now'", true, $this->aliasValue ),
                SQLType::ORACLE => new BasicExpression( 'SYSDATE', '', false, $this->aliasValue  ),
                SQLType::SQLSERVER => new BasicExpression( 'CURRENT_TIMESTAMP', '', false, $this->aliasValue  ),
                default => new BasicExpression( 'NOW', '', true, $this->aliasValue ) // MySQL, PostgreSQL
            };
            return $e->toString( $sqlType );
        }

    };
}


function date(): AliasableExpression {
    return new class extends AliasableExpression {

        public function toString( SQLType $sqlType = SQLType::NONE ): string {
            $e = match ( $sqlType ) {
                SQLType::ORACLE => new BasicExpression( 'SYSDATE', '', false ),
                SQLType::SQLSERVER => new BasicExpression( 'GETDATE', '', true ),
                default => new BasicExpression( 'CURRENT_DATE', '', false ) // MySQL, PostgreSQL, SQLite
            };
            return $e->toString( $sqlType );
        }

    };
}


function time(): AliasableExpression {
    return new class extends AliasableExpression {

        public function toString( SQLType $sqlType = SQLType::NONE ): string {
            $e = match ( $sqlType ) {
                SQLType::ORACLE, SQLType::SQLSERVER => new BasicExpression( 'CURRENT_TIMESTAMP', '', false ),
                default => new BasicExpression( 'CURRENT_TIME', '', false ) // MySQL, PostgreSQL, SQLite
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

class ExtractFunction extends AliasableExpression {

    protected string|ComparableContent|AliasableExpression $dateOrColumn;

    public function __construct( protected Extract $unit ) {}


    public function from( string|ComparableContent|AliasableExpression $dateOrColumn ): ExtractFunction {
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
            SQLType::SQLSERVER => ( new BasicExpression( 'DATEPART', "$unit, $date", true ) )->toString( $sqlType ),
            SQLType::SQLITE => "strftime('%{$unit}', $date)",
            default => "EXTRACT($unit FROM $date)" // MySQL, PostgreSQL, Oracle
        };
    }
}


function extract(
    Extract $unit,
    string|ComparableContent|AliasableExpression $dateOrColumn = ''
): ExtractFunction {
    if ( is_string( $dateOrColumn ) && empty( $dateOrColumn ) ) {
        return ( new ExtractFunction( $unit ) )->from( val( '' ) );
    }
    return ( new ExtractFunction( $unit ) )->from( $dateOrColumn );
}

function diffInDays(
    string|ComparableContent|AliasableExpression $startDate,
    string|ComparableContent|AliasableExpression $endDate
): AliasableExpression {

    return new class ( $startDate, $endDate ) extends AliasableExpression {

        public function __construct(
            protected string|ComparableContent|AliasableExpression $startDate,
            protected string|ComparableContent|AliasableExpression $endDate
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
    string|ComparableContent|AliasableExpression $dateOrColumn,
    int|string|AliasableExpression $value
): AliasableExpression {

    return new class ( $dateOrColumn, $value ) extends AliasableExpression {

        public function __construct(
            protected string|ComparableContent|AliasableExpression $dateOrColumn,
            protected int|string|AliasableExpression $value
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
    string|ComparableContent|AliasableExpression $dateOrColumn,
    int|string|AliasableExpression $value
): AliasableExpression {

    return new class ( $dateOrColumn, $value ) extends AliasableExpression {

        public function __construct(
            protected string|ComparableContent|AliasableExpression $dateOrColumn,
            protected int|string|AliasableExpression $value
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
    string|ComparableContent|AliasableExpression $dateOrColumn,
    int|string|ComparableContent|AliasableExpression $value,
    string $unit = 'day'
): AliasableExpression {

    return new class ( $dateOrColumn, $value, $unit ) extends AliasableExpression {

        public function __construct(
            protected string|ComparableContent|AliasableExpression $dateOrColumn,
            protected int|string|ComparableContent|AliasableExpression $value,
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
    string|ComparableContent|AliasableExpression $dateOrColumn,
    int|string|ComparableContent|AliasableExpression $value,
    string $unit = 'day'
): AliasableExpression {

    return new class ( $dateOrColumn, $value, $unit ) extends AliasableExpression {

        public function __construct(
            protected string|ComparableContent|AliasableExpression $dateOrColumn,
            protected int|string|ComparableContent|AliasableExpression $value,
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
