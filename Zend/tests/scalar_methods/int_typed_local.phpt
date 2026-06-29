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

// Single calls on a typed local.
int $x = 3;
var_dump($x->pow(2));   // 9
var_dump($x->abs());    // 3

// Negative value via a typed local (a bare -5 is a unary-minus expression, not a literal,
// but a typed local holding -5 is still a guaranteed int).
int $n = -5;
var_dump($n->abs());    // 5

// abs() and pow() return int|float, so they are TERMINALS: a typed-local receiver desugars
// the call, but the result does not chain. (For chaining off a typed local, use a string
// typed local with the chainable string methods -- see tier3_typed_local.phpt.)
int $z = -4;
var_dump($z->abs());    // 4

// Re-reading the same typed local desugars consistently.
var_dump($x->abs());    // 3
?>
--EXPECT--
int(9)
int(9)
int(3)
int(5)
int(4)
int(3)
