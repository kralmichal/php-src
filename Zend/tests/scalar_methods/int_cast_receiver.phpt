--TEST--
Scalar methods (int): an (int) cast receiver is a guaranteed int receiver
--FILE--
<?php
// An explicit (int) cast (ZEND_AST_CAST with attr IS_LONG) always yields an int, so the
// compiler desugars `((int)$x)->method()` to Int::method((int)$x, ...).

$y = "-7xyz";
var_dump(((int)$y)->abs());   // (int)"-7xyz" === -7 -> abs -> 7

$f = 3.9;
var_dump(((int)$f)->pow(2));  // (int)3.9 === 3 -> pow(3, 2) === 9

$s = "5";
var_dump(((int)$s)->abs());   // (int)"5" === 5 -> 5
?>
--EXPECT--
int(7)
int(9)
int(5)
