--TEST--
Typed local variables: unsetting a dynamically-written (promoted) typed local does not crash and is balanced
--FILE--
<?php
// Static unset of a typed local promoted by a by-name write.
function a() {
    string $s = 'x';
    $n = 's';
    $$n = 'y';
    unset($s);
    var_dump(isset($s));
    // re-write by name after unset still works (no enforcement once UNDEF; just stored)
    $$n = 'z';
    var_dump($s);
}
a();

// Static unset of a typed local promoted by extract().
function b() {
    int $i = 1;
    extract(['i' => 2]);
    unset($i);
    var_dump(isset($i));
}
b();

// Dynamic unset ($$name) of a promoted typed local.
function c() {
    string $s = 'x';
    $n = 's';
    $$n = 'y';
    $un = 's';
    unset($$un);
    var_dump(isset($s));
}
c();

// A typed local that is both reference-aliased and dynamically written, then unset.
function d() {
    string $s = 'a';
    $r = &$s;             // typed reference, source attached
    $n = 's';
    $$n = 'b';            // by-name write through the same reference
    unset($s);            // drop the typed CV; the alias $r keeps the value
    var_dump(isset($s), $r);
}
d();
?>
--EXPECT--
bool(false)
string(1) "z"
bool(false)
bool(false)
bool(false)
string(1) "b"
