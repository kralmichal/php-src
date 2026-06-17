--TEST--
Scalar methods (int): unary-minus/plus integer literals are guaranteed-int receivers
--FILE--
<?php
// A negative integer literal is parsed as ZEND_AST_UNARY_MINUS over an int literal; unary
// plus likewise. Over a guaranteed-int operand the result is a guaranteed int, so such a
// receiver desugars to the Int backing class (this is literal notation, not arithmetic).

// Negative literal receiver.
var_dump((-5)->abs());        // 5
var_dump((-5)->pow(2));       // 25

// Unary plus.
var_dump((+5)->abs());        // 5

// Nested unary minus recurses to a guaranteed int.
var_dump((- -7)->abs());      // 7

// Chains: abs() returns int and chains into pow().
var_dump((-5)->abs()->pow(2)); // 25

// Counter-cases that must NOT desugar (left as method-call-on-scalar errors):
// - a float literal receiver (unary minus over a float operand is not a guaranteed int)
try { (-3.0)->abs(); } catch (\Error $e) { echo $e->getMessage(), "\n"; }
// - arithmetic (binary op) is not literal notation and is excluded
try { (2 + 1)->abs(); } catch (\Error $e) { echo $e->getMessage(), "\n"; }
?>
--EXPECT--
int(5)
int(25)
int(5)
int(7)
int(25)
Call to a member function abs() on float
Call to a member function abs() on int
