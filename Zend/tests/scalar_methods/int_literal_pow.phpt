--TEST--
Scalar methods (int): pow/abs on an int-literal receiver dispatch to the Int backing class
--FILE--
<?php
// An int-literal receiver (ZEND_AST_ZVAL holding IS_LONG) is a guaranteed int, so the
// compiler desugars `(3)->pow(2)` to Int::pow(3, 2), exactly as string literals desugar
// to Str::method(...).

// Headline: (3)->pow(2) === 9
var_dump((3)->pow(2));

// abs() on an int literal.
var_dump((3)->abs());

// pow() returns int|float: pow(2, -1) === 0.5 (terminal, not a chainable int).
var_dump((2)->pow(-1));

// A larger literal base.
var_dump((10)->pow(3));

// abs() returns int|float: abs(PHP_INT_MIN) overflows to float (the magnitude exceeds
// PHP_INT_MAX), exactly as the global abs(int): int|float does. Int::abs is therefore declared
// int|float (a terminal) -- so this returns a float and does NOT raise a return-type TypeError.
$min = PHP_INT_MIN;
var_dump(((int)$min)->abs());
?>
--EXPECT--
int(9)
int(3)
float(0.5)
int(1000)
float(9.223372036854776E+18)
