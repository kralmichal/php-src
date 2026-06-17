--TEST--
Typed local variables: dynamic writes are enforced at file scope (eager symbol table)
--FILE--
<?php
int $x = 1;
$n = 'x';
$$n = "5";              // coercible -> int(5)
var_dump($x);

string $s = 'a';
extract(['s' => 'b']);  // valid
var_dump($s);

int $i = 1;
try {
    extract(['i' => 'not an int']);
} catch (\TypeError $e) {
    echo $e->getMessage(), "\n";
}
var_dump($i);

$u = 1;                 // untyped at file scope: unchanged
$un = 'u';
$$un = "kept";
var_dump($u);
?>
--EXPECT--
int(5)
string(1) "b"
Cannot assign string to reference held by local variable $i of type int
int(1)
string(4) "kept"
