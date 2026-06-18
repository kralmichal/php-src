--TEST--
Typed local variables: a file-scope typed local imported via the `global` keyword is enforced
--FILE--
<?php
// `global $g` binds the file-scope global $g into the function-local $g by reference.
// When the global is a file-scope typed local, the bind must share a *typed* reference so
// writes through the imported alias honor the declared type (coerce in weak mode, throw on
// a non-coercible value), not overwrite the slot unchecked. This is the cross-function
// import (the same-scope `global $x` on a typed $x is a separate compile error, pinned by
// ref_forbid_global.phpt).

int $g = 1;
string $gs = 'x';
?int $ng = null;
$u = 1;

// weak coercion: "5" -> int(5)
function set_gi_coerce() { global $g; $g = "5"; }
set_gi_coerce();
var_dump($g);

// exact value
function set_gs() { global $gs; $gs = "hello"; }
set_gs();
var_dump($gs);

// nullable accepts int and null
function set_ng_int() { global $ng; $ng = 7; }
set_ng_int();
var_dump($ng);
function set_ng_null() { global $ng; $ng = null; }
set_ng_null();
var_dump($ng);

// wrong type (non-coercible) -> TypeError, slot unchanged
function set_gs_bad() {
    global $gs;
    try {
        $gs = [1, 2, 3];
    } catch (\TypeError $e) {
        echo $e->getMessage(), "\n";
    }
}
set_gs_bad();
var_dump($gs);

function set_gi_bad() {
    global $g;
    try {
        $g = "not a number";
    } catch (\TypeError $e) {
        echo $e->getMessage(), "\n";
    }
}
set_gi_bad();
var_dump($g);

// compound assign / inc-dec through the imported global are enforced too
function add_gi() { global $g; $g += 10; }   // int(5) -> int(15)
add_gi();
var_dump($g);
function inc_gi() { global $g; $g++; }        // -> int(16)
inc_gi();
var_dump($g);
function concat_gi() {
    global $g;
    try {
        $g .= "abc";                          // string concat into int local -> TypeError
    } catch (\TypeError $e) {
        echo $e->getMessage(), "\n";
    }
}
concat_gi();
var_dump($g);

// reading a typed local through the `global` keyword is unchanged
function read_gs() { global $gs; return $gs; }
var_dump(read_gs());

// an untyped global imported via `global` is unchanged (no promotion, no check)
function set_untyped() { global $u; $u = [1, 2, 3]; }
set_untyped();
var_dump($u);
?>
--EXPECT--
int(5)
string(5) "hello"
int(7)
NULL
Cannot assign array to reference held by local variable $gs of type string
string(5) "hello"
Cannot assign string to reference held by local variable $g of type int
int(5)
int(15)
int(16)
Cannot assign string to reference held by local variable $g of type int
int(16)
string(5) "hello"
array(3) {
  [0]=>
  int(1)
  [1]=>
  int(2)
  [2]=>
  int(3)
}
