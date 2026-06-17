--TEST--
Typed local variables: untyped variables are unaffected by the new reference paths
--DESCRIPTION--
The enforcement added to ZEND_ADD_ARRAY_ELEMENT, ZEND_ASSIGN_OBJ_REF,
ZEND_ASSIGN_STATIC_PROP_REF and ZEND_YIELD is gated on the CV being a typed local
(cv_types[i] != NULL). An untyped variable aliased through any of those paths keeps the
ordinary by-reference semantics: any-type writes go through unchecked. Behaves identically
with and without opcache. Values are taken through parameters so the opcache optimizer
cannot constant-fold the observation.
--FILE--
<?php
class Box { public $p; }
class C { public static $s; }

// array-literal reference, untyped: a string write through the element sticks.
function arr($init, $nv) {
    $u = $init;
    $arr = [&$u];
    $arr[0] = $nv;
    var_dump($u);
}
arr(5, "x");

// object-property reference, untyped.
function obj($init, $nv) {
    $u = $init;
    $o = new Box();
    $o->p = &$u;
    $o->p = $nv;
    var_dump($u);
}
obj(5, "x");

// static-property reference, untyped.
function static_ref($init, $nv) {
    $u = $init;
    C::$s = &$u;
    C::$s = $nv;
    var_dump($u);
}
static_ref(5, "x");

// by-reference yield, untyped: write through the yielded reference sticks.
function &gen($init) {
    $u = $init;
    yield $u;
}
foreach (gen(5) as &$v) {
    $v = "x";
}
echo "yield-untyped ok\n";

// uninitialized untyped local through a new path is NOT forbidden (only typed locals are):
// taking a write-reference to an undefined variable auto-vivifies it to a null reference.
function uninit_untyped($nv) {
    $arr = [&$u];      // $u undefined/untyped: allowed, becomes a null reference
    $arr[0] = $nv;
    var_dump($u);
}
uninit_untyped("x");
?>
--EXPECT--
string(1) "x"
string(1) "x"
string(1) "x"
yield-untyped ok
string(1) "x"
