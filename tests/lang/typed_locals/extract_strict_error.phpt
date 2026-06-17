--TEST--
Typed local variables: extract() honors strict_types=1 and rejects a coercible wrong-type value
--FILE--
<?php
declare(strict_types=1);

function reject_string() {
    string $s = 'a';
    try {
        extract(['s' => 5]);
    } catch (\TypeError $e) {
        echo $e->getMessage(), "\n";
    }
    var_dump($s);
}
reject_string();

function reject_int() {
    int $i = 1;
    try {
        extract(['i' => '7']);
    } catch (\TypeError $e) {
        echo $e->getMessage(), "\n";
    }
    var_dump($i);
}
reject_int();

// EXTR_IF_EXISTS writes the existing typed local directly, so strict still rejects.
function reject_if_exists() {
    int $i = 1;
    try {
        extract(['i' => '7'], EXTR_IF_EXISTS);
    } catch (\TypeError $e) {
        echo $e->getMessage(), "\n";
    }
    var_dump($i);
}
reject_if_exists();

// A non-coercible value is rejected under strict as well (soundness, both modes).
function reject_noncoercible() {
    string $s = 'a';
    try {
        extract(['s' => [1, 2]]);
    } catch (\TypeError $e) {
        echo $e->getMessage(), "\n";
    }
    var_dump($s);
}
reject_noncoercible();
?>
--EXPECTF--
Cannot assign int to reference held by local variable $s of type string
string(1) "a"
Cannot assign string to reference held by local variable $i of type int
int(1)
Cannot assign string to reference held by local variable $i of type int
int(1)
Cannot assign array to reference held by local variable $s of type string
string(1) "a"
