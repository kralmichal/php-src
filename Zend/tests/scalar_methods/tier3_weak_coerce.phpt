--TEST--
Scalar methods (Tier 3): weak-mode coercion still yields a real string receiver
--FILE--
<?php
// No strict_types: assigning an int to a `string` typed local coerces it to a real
// string on the write (5 -> "5"). So at the read $s genuinely holds a string and the
// desugared Str::upper($s) operates on "5".
string $s = 5;
var_dump($s->upper());

string $f = 1.5;
var_dump($f->upper());
?>
--EXPECT--
string(1) "5"
string(3) "1.5"
