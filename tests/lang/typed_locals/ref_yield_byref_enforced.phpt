--TEST--
Typed local variables: enforced through a by-reference yield (yield $a in a by-ref generator)
--DESCRIPTION--
In a by-reference generator (function &gen()), `yield $cv` wraps the typed local into the
reference held by the generator's current value (ZEND_YIELD). That reference is reachable
for a write via `foreach ($gen as &$v)`, so it must be type-checked; an uninitialized typed
local must be rejected before the slot is wrapped; a coercible write must still succeed.
Behaves identically with and without opcache.
--FILE--
<?php
// 1) Wrong-type write through the yielded reference is rejected; value unchanged.
function &g_enforce() {
    int $a = 5;
    yield $a;
    var_dump($a); // observed after resume
}
$gen = g_enforce();
try {
    foreach ($gen as &$v) {
        $v = "abc";
    }
} catch (\TypeError $e) {
    echo $e->getMessage(), "\n";
}

// 2) A coercible write through the yielded reference succeeds and is coerced.
function &g_coerce($init) {
    int $a = $init;
    yield $a;
    var_dump($a);
}
foreach (g_coerce(5) as &$v) {
    $v = "9";
}

// 3) Uninitialized typed local cannot be wrapped into a yielded reference.
function &g_uninit() {
    int $a;
    yield $a;
}
try {
    foreach (g_uninit() as &$v) {
        $v = 1;
    }
} catch (\Error $e) {
    echo $e->getMessage(), "\n";
}

echo "after\n";
?>
--EXPECT--
Cannot assign string to reference held by local variable $a of type int
int(9)
Cannot access uninitialized local variable $a by reference
after
