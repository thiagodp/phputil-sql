<?php
namespace phputil\sql;

require_once __DIR__ . '/../internal.php';

// ----------------------------------------------------------------------------
// LOGICAL UTILITIES
// ----------------------------------------------------------------------------

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
