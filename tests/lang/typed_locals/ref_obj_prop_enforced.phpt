--TEST--
Typed local variables: enforced through an object-property reference ($o->p = &$a)
--DESCRIPTION--
Aliasing a typed local into an object property ($o->p = &$cv) makes the property and the
local share a reference (ZEND_ASSIGN_OBJ_REF, OP_DATA = the CV source). A later write
through the property must be type-checked, an uninitialized typed local must be rejected
before the slot is wrapped, and a coercible write must still succeed. Behaves identically
with and without opcache. Values that must survive a write are taken through parameters so
the opcache optimizer cannot constant-fold the observation.
--FILE--
<?php
class Box { public $p; }

// 1) Wrong-type write through the property reference is rejected; value unchanged.
function enforce($bad) {
    int $a = 5;
    $o = new Box();
    $o->p = &$a;
    try {
        $o->p = $bad;
    } catch (\TypeError $e) {
        echo $e->getMessage(), "\n";
    }
    var_dump($a);
}
enforce("abc");

// 2) A coercible write through the property succeeds and is coerced.
function coerce($init, $nv) {
    int $a = $init;
    $o = new Box();
    $o->p = &$a;
    $o->p = $nv;
    var_dump($a);
}
coerce(5, "9");

// 3) Writing through the original local is enforced too (shared reference).
function enforce_original($init, $bad) {
    int $a = $init;
    $o = new Box();
    $o->p = &$a;
    try {
        $a = $bad;
    } catch (\TypeError $e) {
        echo $e->getMessage(), "\n";
    }
    var_dump($a);
}
enforce_original(5, []);

// 4) Uninitialized typed local cannot be wrapped into a property reference.
function uninit() {
    int $a;
    $o = new Box();
    $o->p = &$a;
}
try {
    uninit();
} catch (\Error $e) {
    echo $e->getMessage(), "\n";
}

echo "after\n";
?>
--EXPECT--
Cannot assign string to reference held by local variable $a of type int
int(5)
int(9)
Cannot assign array to local variable $a of type int
int(5)
Cannot access uninitialized local variable $a by reference
after
