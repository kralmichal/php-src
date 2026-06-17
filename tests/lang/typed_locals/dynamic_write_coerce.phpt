--TEST--
Typed local variables: by-name dynamic write ($$name) is type-checked and coerced (weak mode)
--FILE--
<?php
function f() {
    int $x = 1;
    $n = 'x';
    $$n = "5";          // coercible -> int(5)
    var_dump($x);

    string $s = 'a';
    $sn = 's';
    $$sn = 5;           // coercible -> string("5")
    var_dump($s);
}
f();
?>
--EXPECT--
int(5)
string(1) "5"
