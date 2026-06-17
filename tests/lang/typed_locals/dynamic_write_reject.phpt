--TEST--
Typed local variables: by-name dynamic write ($$name) of a non-coercible value throws TypeError (weak mode)
--FILE--
<?php
function f() {
    int $x = 1;
    $n = 'x';
    try {
        $$n = "not-a-number";
    } catch (\TypeError $e) {
        echo $e->getMessage(), "\n";
    }
    var_dump($x);

    try {
        $$n = [];
    } catch (\TypeError $e) {
        echo $e->getMessage(), "\n";
    }
    var_dump($x);
}
f();
?>
--EXPECT--
Cannot assign string to reference held by local variable $x of type int
int(1)
Cannot assign array to reference held by local variable $x of type int
int(1)
