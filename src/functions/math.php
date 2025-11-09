<?php
namespace phputil\sql;

require_once __DIR__ . '/../internal.php';

// ----------------------------------------------------------------------------
// MATHEMATICAL FUNCTIONS
// ----------------------------------------------------------------------------

function abs( int|float|string|ComparableContent|AliasableExpression $valueOrColumn ): AliasableExpression {
    return new BasicExpression( 'ABS', $valueOrColumn, true );
}

function round(
    int|float|string|ComparableContent|AliasableExpression $valueOrColumn,
    ?int $decimals = null
): AliasableExpression {

    return new class ( $valueOrColumn, $decimals ) extends BasicExpression {

        public function __construct(
            int|float|string|ComparableContent|AliasableExpression $valueOrColumn,
            protected ?int $decimals = null
        ) {
            parent::__construct( 'ROUND', $valueOrColumn, true );
        }

        protected function getArgument( SQLType $sqlType ): string {
            $arg = parent::getArgument( $sqlType );
            $decimals = $this->decimals;
            if ( $decimals === null ) {
                return $arg;
            }
            return $arg . ', ' . $decimals;
        }
    };
}

function ceil( int|float|string|ComparableContent|AliasableExpression $valueOrColumn ): AliasableExpression {
    return new class ( $valueOrColumn ) extends AliasableExpression {

        public function __construct(
            protected int|float|string|ComparableContent|AliasableExpression $valueOrColumn
            ) {}

        public function toString( SQLType $sqlType = SQLType::NONE ): string {
            $valueOrColumn = __parseExpression( $this->valueOrColumn, $sqlType );
            $f = match( $sqlType ) {
                SQLType::SQLSERVER => "CEILING($valueOrColumn)",
                default => "CEIL($valueOrColumn)"
            };
            return $f . $this->makeAlias( $sqlType );
        }
    };
}

function floor( int|float|string|ComparableContent|AliasableExpression $valueOrColumn ): AliasableExpression {
    return new BasicExpression( 'FLOOR', $valueOrColumn, true );
}

function power(
    int|float|string|ComparableContent|AliasableExpression $base,
    int|float|string|ComparableContent|AliasableExpression $exponent
): AliasableExpression {
    return new class ( $base, $exponent ) extends AliasableExpression {

        public function __construct(
            protected int|float|string|ComparableContent|AliasableExpression $base,
            protected int|float|string|ComparableContent|AliasableExpression $exponent
            ) {}

        public function toString( SQLType $sqlType = SQLType::NONE ): string {
            $base = __parseExpression( $this->base, $sqlType );
            $exponent = __parseExpression( $this->exponent, $sqlType );
            return "POWER($base, $exponent)" . $this->makeAlias( $sqlType );
        }
    };
}

function sqrt( int|float|string|ComparableContent|AliasableExpression $valueOrColumn ): AliasableExpression {
    return new BasicExpression( 'SQRT', $valueOrColumn, true );
}

function sin( int|float|string|ComparableContent|AliasableExpression $valueOrColumn ): AliasableExpression {
    return new BasicExpression( 'SIN', $valueOrColumn, true );
}

function cos( int|float|string|ComparableContent|AliasableExpression $valueOrColumn ): AliasableExpression {
    return new BasicExpression( 'COS', $valueOrColumn, true );
}

function tan( int|float|string|ComparableContent|AliasableExpression $valueOrColumn ): AliasableExpression {
    return new BasicExpression( 'TAN', $valueOrColumn, true );
}
