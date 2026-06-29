--TEST--
Scalar methods: length() returns int and chains as a guaranteed int receiver
--FILE--
<?php
// length() desugars to Str::length() and is declared to return int. The result of a
// guaranteed-scalar method call is therefore a guaranteed int, so it chains into the int
// methods -- exactly like an int-returning function receiver (strlen($s)->pow(2)) already
// does. This keeps scalar-method returns and function returns consistent.

// Single call: returns int.
var_dump("hello"->length());

// Cross-type chain: "hi"->length() is a guaranteed int(2), so ->pow(2) dispatches to the
// Int backing class: 2 ** 2 === 4.
var_dump("hi"->length()->pow(2));

// The chain dispatches as an INT, not a string: upper() is a Str method, not an Int method,
// so chaining it onto the int result errors as an undefined method on the (internal, NUL-
// named) Int backing class -- proving the receiver type switched from string to int.
"hi"->length()->upper();
?>
--EXPECTF--
int(5)
int(4)

Fatal error: Uncaught Error: Call to undefined method ::upper() in %s:%d
Stack trace:
#0 {main}
  thrown in %s on line %d
