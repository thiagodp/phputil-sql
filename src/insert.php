<?php
namespace phputil\sql;

require_once 'internal.php';

use \Stringable; // PHP 8.0+

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
