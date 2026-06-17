--TEST--
Scalar methods (Tier 3): a non-nullable string typed local is a string receiver
--FILE--
<?php
// A local declared `string $s` is type-enforced on every write, so its value is a
// guaranteed string at any read. The compiler desugars `$s->method()` to
// Str::method($s, ...) exactly as it does for string literals.

// Headline: single call.
string $s = "  hi  ";
var_dump($s->trim());

// Headline: chained dispatch (trim then upper), each level desugars.
string $h = "  Hello World  ";
var_dump($h->trim()->upper());

// Chaining with an argument forwarded after the receiver, plus a further chain.
string $a = "--Hi--";
var_dump($a->trim("-")->upper());

// Re-reading the same typed local desugars consistently.
var_dump($s->trim());
?>
--EXPECT--
string(2) "hi"
string(11) "HELLO WORLD"
string(2) "HI"
string(2) "hi"
