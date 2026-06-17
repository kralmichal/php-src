--TEST--
Typed local variables: taking a reference to an UNINITIALIZED typed local is forbidden
--DESCRIPTION--
Wrapping an uninitialized typed local (its CV slot is still IS_UNDEF) into a
reference would attach the local's synthesized type to a reference holding an
uninitialized value. This is forbidden at every reference-creation chokepoint
that attaches the type source (ZEND_ASSIGN_REF, by-ref argument passing via
ZEND_SEND_REF / ZEND_SEND_VAR_EX, and ZEND_MAKE_REF for a by-ref return guarded
by finally). The discriminator is initialized-vs-uninitialized, not
nullable-vs-not: an uninitialized ?int throws too, while an initialized one
(even when initialized to null) is allowed. The thrown Error is catchable and
behaves identically with and without opcache.
--FILE--
<?php
// 1) $r = &$a (ZEND_ASSIGN_REF), uninitialized int.
function alias_uninit() {
    int $a;
    $r = &$a;
}
try {
    alias_uninit();
} catch (\Error $e) {
    echo $e->getMessage(), "\n";
}

// 2) alternate spelling $x =& $a -> same opcode.
function alias_uninit_alt() {
    int $a;
    $x =& $a;
}
try {
    alias_uninit_alt();
} catch (\Error $e) {
    echo $e->getMessage(), "\n";
}

// 3) appending by reference ($arr[] = &$a) compiles to ASSIGN_REF on the CV source.
function alias_uninit_append() {
    int $a;
    $arr = [];
    $arr[] = &$a;
}
try {
    alias_uninit_append();
} catch (\Error $e) {
    echo $e->getMessage(), "\n";
}

// 4) by-reference argument passing of an uninitialized typed local (ZEND_SEND_REF).
function takes_ref(&$p) { $p = 1; }
function pass_uninit_byref() {
    int $a;
    takes_ref($a);
}
try {
    pass_uninit_byref();
} catch (\Error $e) {
    echo $e->getMessage(), "\n";
}

// 5) nullable uninitialized is forbidden too (UNDEF, not IS_NULL).
function alias_uninit_nullable() {
    ?int $a;
    $r = &$a;
}
try {
    alias_uninit_nullable();
} catch (\Error $e) {
    echo $e->getMessage(), "\n";
}

// 6) by-reference return guarded by finally compiles to ZEND_MAKE_REF on the CV.
function &byref_return_uninit() {
    int $a;
    try {
        return $a;
    } finally {
    }
}
try {
    $r = &byref_return_uninit();
} catch (\Error $e) {
    echo $e->getMessage(), "\n";
}

// The Error is catchable: execution continues normally afterwards.
echo "after\n";

// Positive: an INITIALIZED typed local may be aliased, and the type stays enforced.
function alias_initialized() {
    int $a = 5;
    $r = &$a;
    $r = 9;
    var_dump($a);
}
alias_initialized();

// Positive: an INITIALIZED nullable typed local (initialized to null) may be aliased.
function alias_initialized_nullable() {
    ?int $a = null;
    $r = &$a;
    $r = 7;
    var_dump($a);
}
alias_initialized_nullable();

// Positive: by-ref param of an INITIALIZED typed local works and stays enforced.
function bump(&$p) { $p = 11; }
function pass_initialized_byref() {
    int $a = 1;
    bump($a);
    var_dump($a);
    try {
        $a = &$a; // no-op self alias on an initialized local
    } catch (\Error $e) {
        echo "unexpected: ", $e->getMessage(), "\n";
    }
}
pass_initialized_byref();
?>
--EXPECTF--
Cannot access uninitialized local variable $a by reference
Cannot access uninitialized local variable $a by reference
Cannot access uninitialized local variable $a by reference
Cannot access uninitialized local variable $a by reference
Cannot access uninitialized local variable $a by reference
Cannot access uninitialized local variable $a by reference
after
int(9)
int(7)
int(11)
