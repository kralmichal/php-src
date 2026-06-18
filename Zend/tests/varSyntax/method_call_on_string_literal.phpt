--TEST--
Method call on string literal dispatches to the Str backing class (scalar methods)
--FILE--
<?php
var_dump("  string  "->trim());
var_dump("string"->length());
var_dump("ABC"->lower());
// An unknown method errors as an undefined method on the internal backing class, not
// "member function on string". The backing class has an internal-only name beginning
// with a NUL byte, so it renders empty (not "Str") in the message.
"string"->noSuchMethod();
?>
--EXPECTF--
string(6) "string"
int(6)
string(3) "abc"

Fatal error: Uncaught Error: Call to undefined method ::noSuchMethod() in %s:%d
Stack trace:
#0 {main}
  thrown in %s on line %d
