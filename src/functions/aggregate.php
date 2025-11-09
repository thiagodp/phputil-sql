<?php
namespace phputil\sql;

require_once __DIR__ . '/../internal.php';

// ----------------------------------------------------------------------------
// AGGREGATE FUNCTIONS
// ----------------------------------------------------------------------------

function count( string|ComparableContent|AliasableExpression $column, string $alias = '' ): AggregateFunction {
    return new AggregateFunction( 'COUNT', $column, $alias, false );
}

function countDistinct( string|ComparableContent|AliasableExpression $column, string $alias = '' ): AggregateFunction {
    return new AggregateFunction( 'COUNT', $column, $alias, true );
}

function sum( string|ComparableContent|AliasableExpression $column, string $alias = '' ): AggregateFunction {
    return new AggregateFunction( 'SUM', $column, $alias, false );
}

function sumDistinct( string|ComparableContent|AliasableExpression $column, string $alias = '' ): AggregateFunction {
    return new AggregateFunction( 'SUM', $column, $alias, true );
}

function avg( string|ComparableContent|AliasableExpression $column, string $alias = '' ): AggregateFunction {
    return new AggregateFunction( 'AVG', $column, $alias, false );
}

function avgDistinct( string|ComparableContent|AliasableExpression $column, string $alias = '' ): AggregateFunction {
    return new AggregateFunction( 'AVG', $column, $alias, true );
}

function min( string|ComparableContent|AliasableExpression $column, string $alias = '' ): AggregateFunction {
    return new AggregateFunction( 'MIN', $column, $alias, false );
}

function max( string|ComparableContent|AliasableExpression $column, string $alias = '' ): AggregateFunction {
    return new AggregateFunction( 'MAX', $column, $alias, false );
}
