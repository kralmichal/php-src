--TEST--
Scalar methods (int): unary minus/plus over an integer LITERAL is a guaranteed-int receiver
--FILE--
<?php
// `-5` parses as ZEND_AST_UNARY_MINUS over the int literal `5`; unary plus likewise. Over an
// integer LITERAL the result is always a valid int -- a literal is in [0, PHP_INT_MAX], so
// negating it cannot overflow -- so it desugars to the Int backing class. This is literal
// notation, not arithmetic.
var_dump((-5)->abs());         // 5
var_dump((-5)->pow(2));        // 25
var_dump((+5)->abs());         // 5
var_dump((-5)->abs()->pow(2)); // 25 -- abs():int chains into pow

// Counter-cases that must NOT desugar:
// - a float literal operand is not an int literal
try { (-3.0)->abs(); } catch (\Error $e) { echo $e->getMessage(), "\n"; }
// - arithmetic (binary op) is not literal notation
try { (2 + 1)->abs(); } catch (\Error $e) { echo $e->getMessage(), "\n"; }
// - a NON-literal int operand: `-(int)$x` is int|float, because `-PHP_INT_MIN` overflows to a
//   float at runtime, so it is not a guaranteed single scalar and must fall through. With
//   PHP_INT_MIN the value is actually a float -> "member function on float".
$x = PHP_INT_MIN;
try { (-(int)$x)->abs(); } catch (\Error $e) { echo $e->getMessage(), "\n"; }
// - nested unary: the outer operand is a UNARY_MINUS node, not a literal, so it falls through.
$y = 7;
try { (- -(int)$y)->abs(); } catch (\Error $e) { echo $e->getMessage(), "\n"; }
?>
--EXPECT--
int(5)
int(25)
int(5)
int(25)
Call to a member function abs() on float
Call to a member function abs() on int
Call to a member function abs() on float
Call to a member function abs() on int
