<?php
namespace phputil\sql;

require_once __DIR__ . '/../internal.php';

// ----------------------------------------------------------------------------
// NULL HANDLING FUNCTIONS
// ----------------------------------------------------------------------------

function ifNull(
    string|ComparableContent|AliasableExpression $valueOrColumm,
    bool|int|float|string|ComparableContent|AliasableExpression $valueOrColumnIfNull
): AliasableExpression {
    return new class ( $valueOrColumm, $valueOrColumnIfNull ) extends AliasableExpression {

        public function __construct(
            protected string|ComparableContent|AliasableExpression $valueOrColumm,
            protected bool|int|float|string|ComparableContent|AliasableExpression $valueOrColumnIfNull
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
