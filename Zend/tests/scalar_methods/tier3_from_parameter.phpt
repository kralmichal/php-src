--TEST--
Scalar methods (Tier 3): typed local fed from a runtime parameter is a string receiver
--FILE--
<?php
// The real use case: a value of unknown content arrives at runtime via a `string`
// parameter, is stored in a `string` typed local, and is then used as a receiver.
// The type is enforced on the write into $s, so $s is a guaranteed string at the
// read and `$s->upper()` desugars to Str::upper($s).
function f(string $in): string {
    string $s = $in;
    return $s->upper();
}

var_dump(f("ab"));
var_dump(f("Hello World"));
?>
--EXPECT--
string(2) "AB"
string(11) "HELLO WORLD"
