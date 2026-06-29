--TEST--
Scalar methods: a string concatenation is a valid scalar-method receiver
--FILE--
<?php
// `a . b` always yields a string regardless of operand types, so the concat
// expression is a compile-time-known string receiver.
$b = "b";
var_dump(("a" . $b)->upper());
// Multi-concat: the whole `$a . "-" . $c` is a (nested) concat, still a string.
$a = "  x ";
$c = " y  ";
var_dump(($a . "-" . $c)->trim());
// The concat result is itself a string receiver, so chaining works off it.
var_dump(("foo" . "bar")->upper()->lower());
?>
--EXPECT--
string(2) "AB"
string(5) "x - y"
string(6) "foobar"
