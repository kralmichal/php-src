--TEST--
Scalar methods: unknown method on a string literal errors as an undefined method on the (internal) backing class
--FILE--
<?php
// A string-literal receiver desugars to a static call on the internal backing class.
// An unknown method therefore produces an "undefined method" error. The backing class
// has an internal-only name beginning with a NUL byte, so it does not print as "Str"
// in the message (the class name renders empty).
"x"->nope();
?>
--EXPECTF--
Fatal error: Uncaught Error: Call to undefined method ::nope() in %s:%d
Stack trace:
#0 {main}
  thrown in %s on line %d
