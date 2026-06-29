--TEST--
Scalar methods (int): only guaranteed-int receivers desugar; untyped vars/arithmetic/nullsafe excluded
--FILE--
<?php
// An untyped variable holding an int is NOT a compile-time guaranteed int, so its method
// call is left untouched and fails as a normal method-on-int error.
try {
    $u = 3;
    $u->pow(2);
} catch (\Error $e) {
    echo $e->getMessage(), "\n";
}

// Arithmetic (`$a + $b`) is optimizer-inferred, NOT a syntactically/declared guaranteed
// int. Per the GUARANTEED-never-inferred principle it must NOT desugar.
try {
    $a = 1; $b = 2;
    ($a + $b)->pow(2);
} catch (\Error $e) {
    echo $e->getMessage(), "\n";
}

// A genuine object's method call works normally (no interference).
class C {
    public function pow($e) { return "object-pow"; }
}
$o = new C();
var_dump($o->pow(2));

// Nullsafe on an int literal is deliberately excluded from desugaring.
try {
    (3)?->pow(2);
} catch (\Error $e) {
    echo $e->getMessage(), "\n";
}
?>
--EXPECT--
Call to a member function pow() on int
Call to a member function pow() on int
string(10) "object-pow"
Call to a member function pow() on int
