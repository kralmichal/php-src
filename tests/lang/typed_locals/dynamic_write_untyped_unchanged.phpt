--TEST--
Typed local variables: untyped CVs are not promoted and keep their exact dynamic-write behavior
--FILE--
<?php
function f() {
    $u = 1;
    $n = 'u';
    $$n = "x";          // untyped: stored verbatim, no coercion, no enforcement
    var_dump($u);

    $u2 = 1;
    extract(['u2' => []]);
    var_dump($u2);
}
f();
?>
--EXPECT--
string(1) "x"
array(0) {
}
