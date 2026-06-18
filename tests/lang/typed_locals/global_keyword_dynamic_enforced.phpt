--TEST--
Typed local variables: a file-scope typed local imported via dynamic `global $$name` is enforced
--FILE--
<?php
// The dynamic form `global $$name` compiles to FETCH_W (ZEND_FETCH_GLOBAL_LOCK) + ASSIGN_REF
// rather than ZEND_BIND_GLOBAL. The FETCH_W must promote the file-scope typed local to a
// typed reference so the reference the bind shares carries the declared type, exactly like
// the static `global $g` form.

int $g = 1;
string $gs = 'x';
$u = 1;

// weak coercion through the dynamic import: "99" -> int(99)
function set_coerce() { $n = 'g'; global $$n; $g = "99"; }
set_coerce();
var_dump($g);

// non-coercible -> TypeError, slot unchanged
function set_bad() {
    $n = 'g';
    global $$n;
    try {
        $g = "nope";
    } catch (\TypeError $e) {
        echo $e->getMessage(), "\n";
    }
}
set_bad();
var_dump($g);

// wrong type into a string local -> TypeError
function set_gs_bad() {
    $n = 'gs';
    global $$n;
    try {
        $gs = [1, 2, 3];
    } catch (\TypeError $e) {
        echo $e->getMessage(), "\n";
    }
}
set_gs_bad();
var_dump($gs);

// compound / inc-dec through the dynamic import are enforced
function add_g() { $n = 'g'; global $$n; $g += 1; }   // int(99) -> int(100)
add_g();
var_dump($g);
function concat_g() {
    $n = 'g';
    global $$n;
    try {
        $g .= "x";
    } catch (\TypeError $e) {
        echo $e->getMessage(), "\n";
    }
}
concat_g();
var_dump($g);

// reading via the dynamic import is unchanged
function read_gs() { $n = 'gs'; global $$n; return $gs; }
var_dump(read_gs());

// an untyped global imported dynamically is unchanged (no promotion, no check)
function set_untyped() { $n = 'u'; global $$n; $u = [1, 2, 3]; }
set_untyped();
var_dump($u);
?>
--EXPECT--
int(99)
Cannot assign string to reference held by local variable $g of type int
int(99)
Cannot assign array to reference held by local variable $gs of type string
string(1) "x"
int(100)
Cannot assign string to reference held by local variable $g of type int
int(100)
string(1) "x"
array(3) {
  [0]=>
  int(1)
  [1]=>
  int(2)
  [2]=>
  int(3)
}
