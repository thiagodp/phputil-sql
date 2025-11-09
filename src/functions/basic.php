<?php
namespace phputil\sql;

require_once __DIR__ . '/../internal.php';

use \Stringable; // PHP 8.0+

// ----------------------------------------------------------------------------
// BASIC FUNCTIONS
// ----------------------------------------------------------------------------

class CommandParam implements Stringable {

    public function __construct(
        public string $value
    ) {
    }

    public function __toString(): string {
        return $this->value;
    }
}


function col( string $name ): ComparableWithColumn {
    return new ComparableWithColumn( new Column( $name ) );
}

function val( mixed $value ): ComparableContent {
    $v = null;
    if ( $value instanceof DateTimeInterface ) {
        $v = new Value( __toDateString( $value ) );
    } else {
        $v = new Value( $value );
    }
    return new ComparableContent( $v );
}

function param( string $value = '?' ): CommandParam {
    $value = trim( $value );
    if ( $value === '' || $value === ':' ) {
        $value = '?';
    } else if ( $value != '?' && ! str_starts_with( $value, ':' ) ) {
        $value = ':' . $value;
    }
    // return val( $value );
    return new CommandParam( $value );
}

function quote( string $value ): string {
    return __toString( $value );
}

function wrap( Condition $c ): Condition {
    return new ConditionWrapper( $c );
}

function not( Condition $c ): Condition {
    return new ConditionWrapper( $c, 'NOT ' );
}
