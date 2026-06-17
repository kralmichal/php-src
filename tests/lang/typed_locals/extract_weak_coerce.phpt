--TEST--
Typed local variables: extract() coerces a coercible value into a typed local in weak mode
--FILE--
<?php
// No declare(strict_types=1): weak mode coerces, mirroring $$name = ... .

function coerce_string() {
    string $s = 'a';
    extract(['s' => 5]);
    var_dump($s);
}
coerce_string();

function coerce_int() {
    int $i = 1;
    extract(['i' => '7']);
    var_dump($i);
}
coerce_int();

function coerce_if_exists() {
    int $i = 1;
    extract(['i' => '7'], EXTR_IF_EXISTS);
    var_dump($i);
}
coerce_if_exists();
?>
--EXPECT--
string(1) "5"
int(7)
int(7)
