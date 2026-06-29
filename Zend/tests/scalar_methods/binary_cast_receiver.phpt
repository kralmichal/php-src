--TEST--
Scalar methods: a (binary) cast is a valid scalar-method receiver
--FILE--
<?php
// The scanner maps the (binary) cast to the same token as (string), so it is a
// ZEND_AST_CAST with attr IS_STRING and is a string receiver. (binary) also
// emits a deprecation notice, which is expected here.
$x = 123;
var_dump(((binary)$x)->upper());
// Chaining works off a (binary) cast receiver too.
$s = "  Mixed  ";
var_dump(((binary)$s)->trim()->lower());
?>
--EXPECTF--
Deprecated: Non-canonical cast (binary) is deprecated, use the (string) cast instead in %s on line 6

Deprecated: Non-canonical cast (binary) is deprecated, use the (string) cast instead in %s on line 9
string(3) "123"
string(5) "mixed"
