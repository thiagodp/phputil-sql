<?php
namespace phputil\sql;

require_once 'internal.php';

use \Stringable; // PHP 8.0+

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

                if ( $valueOrColumn instanceof ComparableContent ||
                    $valueOrColumn instanceof CommandParam ||
                    $valueOrColumn instanceof AggregateFunction ||
                    $valueOrColumn instanceof AliasableExpression
                ) {
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
