--TEST--
Typed local variables: multi-reference frame with a failed by-ref send balances type sources at teardown
--DESCRIPTION--
A frame that mixes several typed-local reference paths and then aborts a by-reference
argument send (passing an uninitialized typed local by reference throws) must still
remove every type source it attached before the frame's references are destroyed.

The abort is the interesting part: ZEND_SEND_REF / ZEND_SEND_VAR_EX throw the
"uninitialized local by reference" error before the call's argument slot is written.
That slot may still hold a stale reference left over from a previous call that reused
the same VM stack frame (here, an earlier successful by-ref send of another typed
local). cleanup_unfinished_calls() frees args 1..op2.num of the aborted call, so the
throwing SEND must mark its argument slot UNDEF first; otherwise it dtors the stale
reference, dropping it to refcount 0 and destroying it while the typed local's type
source is still attached -- zend_reference_destroy() asserts in a debug build
(Zend/zend_variables.c) and corrupts the heap otherwise.

Run in a loop so the teardown imbalance is exercised repeatedly; must exit cleanly
under a debug build with and without opcache/JIT.
--FILE--
<?php
class C { public static $s = 0; }
function setRef(&$r, $v) { $r = $v; }

// 1) The full multi-reference combination: object-property ref, static-property ref,
//    by-ref param, two failed coercing writes through references, and an
//    uninitialized-by-ref send that throws and is caught.
function mixed() {
    int $a = 5; int $b = 5; int $c = 5;
    $o = new stdClass;
    $o->p = &$a;                                   // ASSIGN_OBJ_REF -> typed local
    C::$s = &$b;                                   // ASSIGN_STATIC_PROP_REF -> typed local
    setRef($c, 1);                                 // by-ref param -> typed local
    try { $o->p = "x"; } catch (\TypeError $e) {}  // failed coercing write through a ref
    try { C::$s = []; } catch (\TypeError $e) {}   // another failed write
    C::$s = 0;
    int $u;
    try { setRef($u, 1); } catch (\Error $e) {}    // uninitialized-by-ref send: throws
    return $a + $b + $c;
}
$sum = 0;
for ($i = 0; $i < 2000; $i++) { $sum += mixed(); }
var_dump($sum);

// 2) Minimal trigger: a successful by-ref send (which leaves a reference in the reused
//    arg slot) followed by an aborted by-ref send of an uninitialized typed local.
//    Both stack-frame reuse orders.
function init_then_uninit() {
    int $c = 5;
    setRef($c, 1);
    int $u;
    try { setRef($u, 1); } catch (\Error $e) {}
    return $c;
}
function uninit_then_init() {
    int $u;
    try { setRef($u, 1); } catch (\Error $e) {}
    int $c = 5;
    setRef($c, 1);
    return $c;
}
$ok = 0;
for ($i = 0; $i < 2000; $i++) { $ok += init_then_uninit() + uninit_then_init(); }
var_dump($ok);

// 3) The error message is still thrown for the uninitialized typed local.
int $z;
try { setRef($z, 1); } catch (\Error $e) { echo $e->getMessage(), "\n"; }

echo "DONE\n";
?>
--EXPECT--
int(12000)
int(4000)
Cannot access uninitialized local variable $z by reference
DONE
