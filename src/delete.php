<?php
namespace phputil\sql;

require_once 'internal.php';

use \Stringable; // PHP 8.0+

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
