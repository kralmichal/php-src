--TEST--
Typed local variables: enforced through closure capture by reference (use (&$a))
--DESCRIPTION--
Capturing a typed local by reference into a closure (use (&$cv)) aliases the local
into a zend_reference shared with the closure (ZEND_BIND_LEXICAL, by-ref branch).
A later write through the captured reference -- from inside the closure or from the
enclosing scope -- must be type-checked, mirroring typed properties; an uninitialized
typed local must be rejected before the slot is wrapped; and a coercible write must
still succeed. The captured reference's type source is owned by the creating frame's
op_array: it is attached once at capture and removed once when that frame is torn down
(i_free_compiled_variables), even when the closure outlives the frame. Behaves
identically with and without opcache. Values that must survive a write are taken
through parameters so the opcache optimizer cannot constant-fold the observation.
--FILE--
<?php
// 1) Wrong-type write from INSIDE the closure is rejected; the local is unchanged.
function enforce_inside($bad) {
    int $a = 5;
    $c = function () use (&$a, $bad) { $a = $bad; };
    try {
        $c();
    } catch (\TypeError $e) {
        echo $e->getMessage(), "\n";
    }
    var_dump($a);
}
enforce_inside("abc");

// 2) The enclosing scope still enforces the local while it is captured by reference.
//    A direct write to the (aliased) CV is checked through the typed-local guard and
//    reports the direct-CV message; the value is unchanged.
function enforce_outside($bad) {
    int $a = 5;
    $c = function () use (&$a) { $a = 9; };
    try {
        $a = $bad;
    } catch (\TypeError $e) {
        echo $e->getMessage(), "\n";
    }
    var_dump($a);
}
enforce_outside("abc");

// 3) A coercible write through the captured reference succeeds and is coerced.
function coerce($init, $nv) {
    int $a = $init;
    $c = function () use (&$a, $nv) { $a = $nv; };
    $c();
    var_dump($a);
}
coerce(5, "9");

// 4) Uninitialized typed local cannot be captured by reference.
function uninit() {
    int $a;
    $c = function () use (&$a) {};
}
try {
    uninit();
} catch (\Error $e) {
    echo $e->getMessage(), "\n";
}

// 5) Nullable uninitialized is forbidden too (UNDEF, not IS_NULL).
function uninit_nullable() {
    ?int $a;
    $c = function () use (&$a) {};
}
try {
    uninit_nullable();
} catch (\Error $e) {
    echo $e->getMessage(), "\n";
}

// The Error is catchable: execution continues.
echo "after\n";

// 6) Escaped closure: it outlives the creating frame, then is dropped by the caller.
//    The type source is owned by the creating frame's op_array, so it is removed exactly
//    once when that frame is torn down (i_free_compiled_variables) -- the reference itself
//    survives, held by the closure, now WITHOUT a type source. This is the scope-bound
//    rule (same as the by-ref return in ref_paths_complete.phpt): once the local's scope
//    ends the constraint is gone, so writes through the surviving reference are no longer
//    type-checked. What this case asserts is teardown SOUNDNESS: the source is DEL'd once
//    (not zero or twice), there is no dangling source pointer, and using then dropping the
//    escaped closure neither crashes nor leaks (verified under the --enable-debug MM
//    tracker). The value stays an int here only because the closure performs an int += 1.
function make_escaping($init) {
    int $a = $init;
    $c = function () use (&$a) { $a += 1; };
    $c();
    return $c;
}
$esc = make_escaping(10);
$esc();
echo "escaped-ok\n";
unset($esc);
echo "dropped\n";
?>
--EXPECT--
Cannot assign string to reference held by local variable $a of type int
int(5)
Cannot assign string to local variable $a of type int
int(5)
int(9)
Cannot access uninitialized local variable $a by reference
Cannot access uninitialized local variable $a by reference
after
escaped-ok
dropped
