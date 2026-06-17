--TEST--
Typed local variables: enforced through an array-literal reference element ([&$a])
--DESCRIPTION--
Building an array with a by-reference element ($arr = [&$cv]) wraps the typed local
into the reference held by the element (ZEND_ADD_ARRAY_ELEMENT, reached for the first
element via ZEND_INIT_ARRAY's dispatch). A later write through the element must be
type-checked, an uninitialized typed local must be rejected before the slot is wrapped,
and a coercible write must still succeed. Behaves identically with and without opcache.
Values that must survive a write are taken through parameters so the opcache optimizer
cannot constant-fold the observation.
--FILE--
<?php
// 1) Wrong-type write through the array-held reference is rejected; value unchanged.
function enforce($bad) {
    int $a = 5;
    $arr = [&$a];
    try {
        $arr[0] = $bad;
    } catch (\TypeError $e) {
        echo $e->getMessage(), "\n";
    }
    var_dump($a);
}
enforce("abc");

// 2) A coercible write through the element succeeds and is coerced.
function coerce($init, $nv) {
    int $a = $init;
    $arr = [&$a];
    $arr[0] = $nv;
    var_dump($a);
}
coerce(5, "9");

// 3) Keyed and multi-element array literals enforce too.
function enforce_keyed($init_b, $init_c, $bad, $nv_b) {
    float $b = $init_b;
    string $c = $init_c;
    $arr = ['x' => &$b, 'y' => &$c];
    try {
        $arr['y'] = $bad;
    } catch (\TypeError $e) {
        echo $e->getMessage(), "\n";
    }
    $arr['x'] = $nv_b;
    var_dump($b);
    var_dump($c);
}
enforce_keyed(2.0, "s", [], "3.5");

// 4) Uninitialized typed local cannot be wrapped into an array-literal reference.
function uninit() {
    int $a;
    $arr = [&$a];
}
try {
    uninit();
} catch (\Error $e) {
    echo $e->getMessage(), "\n";
}

// 5) Nullable uninitialized is forbidden too (UNDEF, not IS_NULL).
function uninit_nullable() {
    ?int $a;
    $arr = [&$a];
}
try {
    uninit_nullable();
} catch (\Error $e) {
    echo $e->getMessage(), "\n";
}

// The Error is catchable: execution continues.
echo "after\n";
?>
--EXPECT--
Cannot assign string to reference held by local variable $a of type int
int(5)
int(9)
Cannot assign array to reference held by local variable $c of type string
float(3.5)
string(1) "s"
Cannot access uninitialized local variable $a by reference
Cannot access uninitialized local variable $a by reference
after
