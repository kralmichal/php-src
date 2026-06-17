--TEST--
Typed local variables: extract() of a non-coercible value into a typed local always throws (weak mode)
--FILE--
<?php
// No declare(strict_types=1). A non-coercible value (array) is rejected even in weak
// mode, because a typed reference never accepts a conversion. (Strict-mode rejection of
// a non-coercible value is covered by extract_strict_error.phpt.)

function reject_array_into_string() {
    string $s = 'a';
    try {
        extract(['s' => [1, 2]]);
    } catch (\TypeError $e) {
        echo $e->getMessage(), "\n";
    }
    var_dump($s);
}
reject_array_into_string();

function reject_array_into_int() {
    int $i = 1;
    try {
        extract(['i' => [1, 2]]);
    } catch (\TypeError $e) {
        echo $e->getMessage(), "\n";
    }
    var_dump($i);
}
reject_array_into_int();
?>
--EXPECTF--
Cannot assign array to reference held by local variable $s of type string
string(1) "a"
Cannot assign array to reference held by local variable $i of type int
int(1)
