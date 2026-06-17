--TEST--
Scalar methods (int, Tier 3): a non-nullable int typed local is an int receiver (the bridge)
--FILE--
<?php
// A local declared `int $x` is type-enforced on every write, so its value is a guaranteed
// int at any read. The compiler desugars `$x->method()` to Int::method($x, ...), exactly
// as it does for int literals. This is the typed-local bridge.

// Headline (the bridge): int $f = 3; $f->pow(2) === 9
function f(): int { int $f = 3; return $f->pow(2); }
var_dump(f());

// Single call on a typed local.
int $x = 3;
var_dump($x->pow(2));
var_dump($x->abs());

// Negative value via a typed local (a bare -5 is a unary-minus expression, not a literal,
// but a typed local holding -5 is still a guaranteed int).
int $n = -5;
var_dump($n->abs());

// Chaining: abs() returns int (chainable), so `$z->abs()->pow(2)` desugars at both levels
// (abs as a chainable int receiver, pow as the terminal call).
int $z = -4;
var_dump($z->abs()->pow(2));   // abs(-4)=4 -> pow(4,2)=16

// abs() chains into abs().
int $w = -3;
var_dump($w->abs()->abs());    // 3

// Re-reading the same typed local desugars consistently.
var_dump($x->abs());
?>
--EXPECT--
int(9)
int(9)
int(3)
int(5)
int(16)
int(3)
int(3)
