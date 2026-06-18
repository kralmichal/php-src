--TEST--
Typed local variables: strict_types=1 rejects a $GLOBALS['name'] write of a wrong-type value
--FILE--
<?php
declare(strict_types=1);

int $gi = 1;

// strict mode: a coercible string is still rejected (matches $$name / static assignment)
try {
    $GLOBALS['gi'] = "5";
} catch (\TypeError $e) {
    echo $e->getMessage(), "\n";
}
var_dump($gi);

// non-coercible value rejected in strict mode too
try {
    $GLOBALS['gi'] = "not a number";
} catch (\TypeError $e) {
    echo $e->getMessage(), "\n";
}
var_dump($gi);

// an exact-type int is accepted
$GLOBALS['gi'] = 7;
var_dump($gi);
?>
--EXPECT--
Cannot assign string to reference held by local variable $gi of type int
int(1)
Cannot assign string to reference held by local variable $gi of type int
int(1)
int(7)
