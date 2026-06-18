--TEST--
Typed local variables: a $GLOBALS['name'] write into a file-scope typed local is enforced
--FILE--
<?php
// $GLOBALS['name'] = ... is a by-name write into the file-scope CV. Like $$name and
// extract(), it must honor the local's declared type (coerce in weak mode, throw on a
// non-coercible value), not overwrite the slot unchecked.

string $gs = 'x';
int $gi = 1;
?int $ng = null;

// weak coercion: "5" -> int(5)
$GLOBALS['gi'] = "5";
var_dump($gi);

// exact value
$GLOBALS['gs'] = "hello";
var_dump($gs);

// nullable accepts null and int
$GLOBALS['ng'] = 7;
var_dump($ng);
$GLOBALS['ng'] = null;
var_dump($ng);

// wrong type (non-coercible) -> TypeError, slot unchanged
try {
    $GLOBALS['gs'] = [1, 2, 3];
} catch (\TypeError $e) {
    echo $e->getMessage(), "\n";
}
var_dump($gs);

try {
    $GLOBALS['gi'] = "not a number";
} catch (\TypeError $e) {
    echo $e->getMessage(), "\n";
}
var_dump($gi);

// compound assign / inc-dec through $GLOBALS are enforced too
$GLOBALS['gi'] += 10;          // int(5) -> int(15)
var_dump($gi);
$GLOBALS['gi']++;              // -> int(16)
var_dump($gi);
try {
    $GLOBALS['gi'] .= "abc";  // string concat into int local -> TypeError
} catch (\TypeError $e) {
    echo $e->getMessage(), "\n";
}
var_dump($gi);

// reading a typed local through $GLOBALS is unchanged
var_dump($GLOBALS['gs']);

// an untyped global written through $GLOBALS is unchanged (no promotion, no check)
$u = 1;
$GLOBALS['u'] = [1, 2, 3];
var_dump($u);
?>
--EXPECT--
int(5)
string(5) "hello"
int(7)
NULL
Cannot assign array to reference held by local variable $gs of type string
string(5) "hello"
Cannot assign string to reference held by local variable $gi of type int
int(5)
int(15)
int(16)
Cannot assign string to reference held by local variable $gi of type int
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
