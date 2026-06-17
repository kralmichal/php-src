--TEST--
Typed local variables: extract() into an untyped variable is unaffected by caller strict_types
--FILE--
<?php
declare(strict_types=1);
// An untyped target is never type-checked: the value is copied verbatim regardless of
// the caller's strict_types mode. This must hold under strict_types=1 (where a typed
// local would throw).

function overwrite() {
    $u = 1;
    extract(['u' => 'x']);
    var_dump($u);
}
overwrite();

function if_exists() {
    $u = 1;
    extract(['u' => 'x'], EXTR_IF_EXISTS);
    var_dump($u);
}
if_exists();

function prefix_all() {
    extract(['u' => 5], EXTR_PREFIX_ALL, 'p');
    var_dump($p_u);
}
prefix_all();

function prefix_invalid() {
    // "1bad" is not a valid name, so it is prefixed; target is a fresh untyped var.
    extract(['1bad' => 'y'], EXTR_PREFIX_INVALID, 'p');
    var_dump($p_1bad);
}
prefix_invalid();
?>
--EXPECT--
string(1) "x"
string(1) "x"
int(5)
string(1) "y"
