--TEST--
Scalar methods: interpolated double-quoted strings and heredocs are receivers
--FILE--
<?php
// An interpolated double-quoted string is an encaps list and always yields a
// string, so it is a valid scalar-method receiver.
$name = "World";
var_dump("Hello $name"->upper());
// Even a single-variable interpolation is an encaps list (not a plain literal).
$pad = "  trim me  ";
var_dump("$pad"->trim());
// A heredoc body with interpolation is likewise a string receiver.
$w = "hi";
var_dump((<<<EOT
  $w there
EOT)->trim());
?>
--EXPECT--
string(11) "HELLO WORLD"
string(7) "trim me"
string(8) "hi there"
