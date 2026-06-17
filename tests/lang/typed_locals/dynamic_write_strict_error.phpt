--TEST--
Typed local variables: strict_types=1 rejects a by-name dynamic write ($$name) of a wrong-type value
--FILE--
<?php
declare(strict_types=1);
function f() {
    string $s = 'a';
    $n = 's';
    try {
        $$n = 5;
    } catch (\TypeError $e) {
        echo $e->getMessage(), "\n";
    }
    var_dump($s);
}
f();
?>
--EXPECTF--
Cannot assign int to reference held by local variable $s of type string
string(1) "a"
