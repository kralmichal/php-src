--TEST--
Typed local variables: declaring a type after the variable has already been used is a compile error
--FILE--
<?php
function f() {
    $x = $x + 1;
    int $x = 5;
}
?>
--EXPECTF--
Fatal error: Cannot declare a type for $x after it has already been used in %s on line %d
