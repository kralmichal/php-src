--TEST--
Typed local variables: by-name compound assign / inc-dec into an uninitialized typed local at file scope (BP_VAR_RW)
--FILE--
<?php
// File (global/main) scope: the symbol table is materialized eagerly, but promotion still
// only happens on the compound/inc-dec write, so enforcement matches function scope.

// Non-coercible compound concat -> TypeError, slot left unset.
int $u;
$n = 'u';
try {
    $$n .= "x";
} catch (\TypeError $e) {
    echo $e->getMessage(), "\n";
}
var_dump(isset($u));

// Weak compound add into an uninitialized string-typed local -> "5".
string $s;
$m = 's';
$$m += 5;
var_dump($s);

// Increment into a fresh int local -> int(1).
int $w;
$k = 'w';
$$k++;
var_dump($w);

// Initialized typed local is enforced (no regression).
int $z = 1;
$j = 'z';
try {
    $$j .= "x";
} catch (\TypeError $e) {
    echo $e->getMessage(), "\n";
}
var_dump($z);

// Untyped at file scope is unchanged.
$t = 1;
$p = 't';
$$p .= "x";
var_dump($t);
?>
--EXPECTF--
Warning: Undefined variable $u in %s on line %d
Cannot assign string to reference held by local variable $u of type int
bool(false)

Warning: Undefined variable $s in %s on line %d
string(1) "5"

Warning: Undefined variable $w in %s on line %d
int(1)
Cannot assign string to reference held by local variable $z of type int
int(1)
string(2) "1x"
