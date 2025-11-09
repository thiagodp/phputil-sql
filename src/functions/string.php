<?php
namespace phputil\sql;

require_once __DIR__ . '/../internal.php';

// ----------------------------------------------------------------------------
// STRING FUNCTIONS
// ----------------------------------------------------------------------------

function upper( string|ComparableContent|AliasableExpression $textOrColumn ): AliasableExpression {
    return new class ( $textOrColumn ) extends AliasableExpression {

        public function __construct(
            protected string|ComparableContent|AliasableExpression $textOrColumn
        ) {}

        public function toString( SQLType $sqlType = SQLType::NONE ): string {
            $textOrColumn = __valueOrName( $this->textOrColumn, $sqlType );
            return "UPPER($textOrColumn)" . $this->makeAlias( $sqlType );
        }
    };
}

function lower( string|ComparableContent|AliasableExpression $textOrColumn ): AliasableExpression {
    return new class ( $textOrColumn ) extends AliasableExpression {

        public function __construct(
            protected string|ComparableContent|AliasableExpression $textOrColumn
        ) {}

        public function toString( SQLType $sqlType = SQLType::NONE ): string {
            $textOrColumn = __valueOrName( $this->textOrColumn, $sqlType );
            return "LOWER($textOrColumn)" . $this->makeAlias( $sqlType );
        }
    };
}

function substring(
    string|ComparableContent|AliasableExpression $textOrColumn,
    int|string $pos = 1,
    int $len = 0
): AliasableExpression {

    return new class ( $textOrColumn, $pos, $len ) extends AliasableExpression {

        public function __construct(
            protected string|ComparableContent|AliasableExpression $textOrColumn,
            protected int|string $pos = 1,
            protected int $len = 0
            ) {}

        public function toString( SQLType $sqlType = SQLType::NONE ): string {
            $textOrColumn = __valueOrName( $this->textOrColumn, $sqlType );
            $pos = $this->pos;
            $len = $this->len;
            $f = match ( $sqlType ) {
                SQLType::POSTGRESQL => ( $len > 0 ? "SUBSTRING($textOrColumn FROM $pos FOR $len)" : "SUBSTRING($textOrColumn FROM $pos)" ),
                SQLType::SQLITE, SQLType::ORACLE => ( $len > 0 ? "SUBSTR($textOrColumn, $pos, $len)" : "SUBSTR($textOrColumn, $pos)" ),
                SQLType::SQLSERVER => "SUBSTRING($textOrColumn, $pos, $len)",
                default => ( $len > 0 ? "SUBSTRING($textOrColumn, $pos, $len)" : "SUBSTRING($textOrColumn, $pos)" ) // MySQL
            };
            return $f . $this->makeAlias( $sqlType );
        }
    };
}


function concat(
    string|ComparableContent|AliasableExpression $textOrColumn1,
    string|ComparableContent|AliasableExpression $textOrColumn2,
    string|ComparableContent|AliasableExpression ...$other
): AliasableExpression {

    return new class (  $textOrColumn1, $textOrColumn2, ...$other ) extends AliasableExpression {

        /** @var string[]|ComparableContent[] */
        protected $other;

        public function __construct(
            protected string|ComparableContent|AliasableExpression $textOrColumn1,
            protected string|ComparableContent|AliasableExpression $textOrColumn2,
            string|ComparableContent|AliasableExpression ...$other
            ) {
            $this->other = $other;
        }

        public function toString( SQLType $sqlType = SQLType::NONE ): string {
            $textOrColumn1 = __valueOrName( $this->textOrColumn1, $sqlType );
            $textOrColumn2 = __valueOrName( $this->textOrColumn2, $sqlType );
            $other = array_map( fn( $s ) => __valueOrName( $s, $sqlType ), $this->other );

            $f = '';
            if ( $sqlType === SQLType::ORACLE ) {
                $f = implode( ' || ', [ $textOrColumn1, $textOrColumn2, ...$other ] );
                $a = $this->makeAlias( $sqlType );
                if ( ! empty( $a ) ) {
                    $f = '(' . $f . ')' . $a;
                }
            } else {
                $params = implode( ', ', [ $textOrColumn1, $textOrColumn2, ...$other ] );
                $f = "CONCAT($params)" . $this->makeAlias( $sqlType );
            }
            return $f;

        }
    };
}


function length( string|ComparableContent|AliasableExpression $textOrColumn ): AliasableExpression {
    return new class ( $textOrColumn ) extends AliasableExpression {

        public function __construct(
            protected string|ComparableContent|AliasableExpression $textOrColumn
            ) {}

        public function toString( SQLType $sqlType = SQLType::NONE ): string {
            $textOrColumn = __valueOrName( $this->textOrColumn, $sqlType );
            $f = match ( $sqlType ) {
                SQLType::SQLSERVER => "LEN($textOrColumn)",
                SQLType::MYSQL, SQLType::POSTGRESQL => "CHAR_LENGTH($textOrColumn)",
                default => "LENGTH($textOrColumn)"
            };
            return $f . $this->makeAlias( $sqlType );
        }
    };
}


function bytes( string|ComparableContent|AliasableExpression $textOrColumn ): AliasableExpression {
    return new class ( $textOrColumn ) extends AliasableExpression {

        public function __construct(
            protected string|ComparableContent|AliasableExpression $textOrColumn
            ) {}

        public function toString( SQLType $sqlType = SQLType::NONE ): string {
            $textOrColumn = __valueOrName( $this->textOrColumn, $sqlType );
            $f = match ( $sqlType ) {
                SQLType::ORACLE => "VSIZE($textOrColumn)",
                SQLType::SQLSERVER => "DATALENGTH($textOrColumn)",
                SQLType::POSTGRESQL => "OCTET_LENGTH($textOrColumn)",
                SQLType::SQLITE => "LENGTH(RTRIM($textOrColumn, CHAR(0)))",
                default => "LENGTH($textOrColumn)" // MySQL
            };
            return $f . $this->makeAlias( $sqlType );
        }
    };
}
