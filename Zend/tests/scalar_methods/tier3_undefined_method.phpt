--TEST--
Scalar methods (Tier 3): unknown method on a typed-local receiver errors as an undefined method on the (internal) backing class
--FILE--
<?php
// A valid non-nullable `string` typed local receiver routes through the desugar, so
// an unknown method resolves against the internal backing class just like a
// string-literal receiver does. The backing class has an internal-only name beginning
// with a NUL byte, so it does not print as "Str" in the message (it renders empty).
string $s = "x";
$s->nope();
?>
--EXPECTF--
Fatal error: Uncaught Error: Call to undefined method ::nope() in %s:%d
Stack trace:
#0 {main}
  thrown in %s on line %d
