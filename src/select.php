<?php
namespace phputil\sql;

require_once 'internal.php';

use \Stringable; // PHP 8.0+

// ----------------------------------------------------------------------------
// SELECT
// ----------------------------------------------------------------------------

class Select implements DBStringable, Stringable {

    /** @var mixed[] */
    protected $columns = [];
    protected ?From $from = null;

    public function __construct(
        protected bool $distinct,
        mixed ...$columns
    ) {
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


function select( mixed ...$columns ): Select {
    return new Select( false, ...$columns );
}

function selectDistinct( mixed ...$columns ): Select {
    return new Select( true, ...$columns );
}
