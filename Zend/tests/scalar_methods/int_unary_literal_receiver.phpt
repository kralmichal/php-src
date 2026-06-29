--TEST--
Scalar methods (int): unary minus/plus over an integer LITERAL is a guaranteed-int receiver
--FILE--
<?php
// `-5` parses as ZEND_AST_UNARY_MINUS over the int literal `5`; unary plus likewise. Over an
// integer LITERAL the result is always a valid int -- a literal is in [0, PHP_INT_MAX], so
// negating it cannot overflow -- so it desugars to the Int backing class. This is literal notation.
var_dump((-5)->abs());         // 5
var_dump((-5)->pow(2));        // 25 -- (-5) is a guaranteed-int receiver; pow desugars
var_dump((+5)->abs());         // 5

// abs() returns int|float (abs(PHP_INT_MIN) overflows to float), so abs is a TERMINAL like pow:
// (-5)->abs() returns int(5), but the result does not chain.
try { (-5)->abs()->pow(2); } catch (\Error $e) { echo $e->getMessage(), "\n"; }

// A unary-minus FLOAT literal is a guaranteed float (negation never overflows), so it desugars
// to Float::abs and returns float(3) -- not an int receiver, but a valid scalar receiver.
var_dump((-3.0)->abs());
// Counter-cases that must NOT desugar:
// - arithmetic (binary op) is not literal notation
try { (2 + 1)->abs(); } catch (\Error $e) { echo $e->getMessage(), "\n"; }
// - a NON-literal int operand: `-(int)$x` is int|float (`-PHP_INT_MIN` overflows), so it falls
//   through; with PHP_INT_MIN the runtime value is a float -> "member function on float".
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
Call to a member function pow() on int
float(3)
Call to a member function abs() on int
Call to a member function abs() on float
Call to a member function abs() on int
