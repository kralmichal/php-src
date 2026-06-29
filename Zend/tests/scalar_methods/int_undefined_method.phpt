--TEST--
Scalar methods (int): unknown method on an int literal errors as undefined method on the Int backing class
--FILE--
<?php
// An int-literal receiver desugars to a static call on the internal Int backing class.
// An unknown method therefore produces an "undefined method" error. The backing class has
// an internal-only name beginning with a NUL byte, so it does not print as "Int" in the
// message (the class name renders empty).
(3)->nope();
?>
--EXPECTF--
Fatal error: Uncaught Error: Call to undefined method ::nope() in %s:%d
Stack trace:
#0 {main}
  thrown in %s on line %d
