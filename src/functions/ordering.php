<?php
namespace phputil\sql;

require_once __DIR__ . '/../internal.php';

// ----------------------------------------------------------------------------
// ORDERING UTILITIES
// ----------------------------------------------------------------------------

function desc( string|AggregateFunction $column ): ColumnOrdering {
    // return $column . ' DESC';
    return new ColumnOrdering( $column, true );
}

function asc( string|AggregateFunction $column ): ColumnOrdering {
    // return $column . ' ASC';
    return new ColumnOrdering( $column, false );
}
