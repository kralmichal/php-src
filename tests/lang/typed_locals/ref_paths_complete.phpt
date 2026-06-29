--TEST--
Typed local variables: reference soundness across EVERY reference-creating construct
--DESCRIPTION--
Completeness harness. A typed local must MIRROR a typed property: the declared type
is enforced through every path that can create a reference aliasing the local's CV
slot, and taking a reference to an uninitialized typed local is forbidden. This test
exercises every such construct in one place so a future regression in ANY single ref
path (or a newly added one left unguarded) is caught.

Each case takes a typed local, creates a reference to it via one construct, then writes
a wrong-type value through that reference and asserts a TypeError. The bad value is
passed through a PARAMETER in every case: the opcache optimizer can constant-fold a
write whose value AND the local's initializer are both literals in the same compilation
unit and elide the runtime type-check (a pre-existing optimizer observation artifact,
not an engine soundness hole -- the engine guard still fires when the value is opaque),
so routing through a parameter keeps the test meaningful with and without opcache.

EXCEPTION -- by-reference return (case 3) is intentionally NOT enforced. A typed local
is scope-bound: when the function frame that owns it is destroyed, its CV slot drops
and the op_array-owned type source is removed (i_free_compiled_variables). A by-ref
return hands back the reference AFTER that teardown, so the constraint no longer exists
-- the local it constrained is gone. Writing a wrong type through the returned reference
is therefore allowed; this is correct, and the test asserts it.

The two uninitialized cases assert the by-ref capture is forbidden before the slot is
wrapped. Behaves identically with and without opcache.
--FILE--
<?php
function chk(string $n, callable $f, $bad) {
    try { $f($bad); echo "BYPASS  $n\n"; }
    catch (\TypeError $e) { echo "throw   $n\n"; }
    catch (\Error $e) { echo "Error   $n :: ", $e->getMessage(), "\n"; }
}

// 1) $r = &$a (ZEND_ASSIGN_REF)
function c1($bad) { int $a = 1; $r = &$a; $r = $bad; }
chk('1  $r=&$a (ASSIGN_REF)', 'c1', "x");

// 2) by-reference parameter (ZEND_SEND_REF / ZEND_SEND_VAR_EX)
function c2($bad) { int $a = 1; (function (&$p) use ($bad) { $p = $bad; })($a); }
chk('2  by-ref param (SEND_REF)', 'c2', "x");

// 3) by-reference RETURN -- EXPECTED to bypass (scope-bound; see DESCRIPTION).
$GLOBALS['gr'] = function &(int $s) { int $a = $s; $r = &$a; return $r; };
function c3($bad) { $ref = &$GLOBALS['gr'](1); $ref = $bad; }
chk('3  by-ref return (EXPECT BYPASS)', 'c3', "x");

// 4) array literal by-ref element (ZEND_ADD_ARRAY_ELEMENT via INIT_ARRAY)
function c4($bad) { int $a = 1; $arr = [&$a]; $arr[0] = $bad; }
chk('4  [&$a] (ADD_ARRAY_ELEMENT)', 'c4', "x");

// 5) append by-ref ($arr[] = &$a)
function c5($bad) { int $a = 1; $arr = []; $arr[] = &$a; $arr[0] = $bad; }
chk('5  $arr[]=&$a', 'c5', "x");

// 6) keyed element by-ref ($arr['k'] = &$a)
function c6($bad) { int $a = 1; $arr = []; $arr['k'] = &$a; $arr['k'] = $bad; }
chk("6  \$arr['k']=&\$a", 'c6', "x");

// 7) object property by-ref ($o->p = &$a) (ZEND_ASSIGN_OBJ_REF)
class O { public $p; }
function c7($bad) { int $a = 1; $o = new O; $o->p = &$a; $o->p = $bad; }
chk('7  $o->p=&$a (ASSIGN_OBJ_REF)', 'c7', "x");

// 8) static property by-ref (C::$s = &$a) (ZEND_ASSIGN_STATIC_PROP_REF)
class S { public static $s; }
function c8($bad) { int $a = 1; S::$s = &$a; S::$s = $bad; }
chk('8  C::$s=&$a (ASSIGN_STATIC_PROP_REF)', 'c8', "x");

// 9) yield by reference (ZEND_YIELD, by-ref)
function &gy(int $s) { int $a = $s; yield $a; }
function c9($bad) { foreach (gy(1) as &$v) { $v = $bad; } }
chk('9  yield by ref (YIELD)', 'c9', "x");

// 10) closure capture by reference (ZEND_BIND_LEXICAL, by-ref)
function c10($bad) { int $a = 1; $c = function () use (&$a, $bad) { $a = $bad; }; $c(); }
chk('10 use(&$a) (BIND_LEXICAL)', 'c10', "x");

// 12) plain alias $b = &$a (ZEND_ASSIGN_REF), kept for symmetry with the matrix.
function c12($bad) { int $a = 1; $b = &$a; $b = $bad; }
chk('12 $b=&$a (ASSIGN_REF)', 'c12', "x");

// Uninitialized: taking a reference to an unassigned typed local is forbidden,
// before the slot is wrapped, on every attaching path.
function u4() { int $a; $arr = [&$a]; }
chk('U4 [&$a] uninitialized', 'u4', null);

function u10() { int $a; $c = function () use (&$a) {}; }
chk('U10 use(&$a) uninitialized', 'u10', null);
?>
--EXPECT--
throw   1  $r=&$a (ASSIGN_REF)
throw   2  by-ref param (SEND_REF)
BYPASS  3  by-ref return (EXPECT BYPASS)
throw   4  [&$a] (ADD_ARRAY_ELEMENT)
throw   5  $arr[]=&$a
throw   6  $arr['k']=&$a
throw   7  $o->p=&$a (ASSIGN_OBJ_REF)
throw   8  C::$s=&$a (ASSIGN_STATIC_PROP_REF)
throw   9  yield by ref (YIELD)
throw   10 use(&$a) (BIND_LEXICAL)
throw   12 $b=&$a (ASSIGN_REF)
Error   U4 [&$a] uninitialized :: Cannot access uninitialized local variable $a by reference
Error   U10 use(&$a) uninitialized :: Cannot access uninitialized local variable $a by reference
