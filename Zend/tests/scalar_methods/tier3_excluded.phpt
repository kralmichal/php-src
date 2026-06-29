--TEST--
Scalar methods (Tier 3): nullable / no-scalar-method / untyped locals do NOT desugar
--FILE--
<?php
// Only a non-nullable typed local of a type that has scalar methods (string -> Str,
// int -> Int) is a guaranteed scalar-method receiver. Each case below must fall through
// to the normal method-call path (NOT route to Str::/Int::), proving the exclusions.

// 1. Nullable `?string` is excluded (it may be null), so no desugar: the normal
//    method-call path errors on the string value.
try {
    ?string $n = "x";
    $n->trim();
} catch (\Error $e) {
    echo $e->getMessage(), "\n";
}

// 2. A typed local of a type with no scalar-method backing class (bool) is excluded:
//    normal "method on bool" error. (string/int/float DO desugar — to Str/Int/Float; bool's
//    operations are language operators, not methods, so it has no backing class.)
try {
    bool $i = true;
    $i->foo();
} catch (\Error $e) {
    echo $e->getMessage(), "\n";
}

// 3. An untyped variable holding a string is NOT a guaranteed compile-time string
//    (the engine cannot prove its type), so it is excluded: normal "method on
//    string" error, identical to today's behavior for untyped vars.
try {
    $u = "hi";
    $u->trim();
} catch (\Error $e) {
    echo $e->getMessage(), "\n";
}

// 4. A nullable local whose declared value happens to be non-null is still excluded
//    purely on its declared (nullable) type.
try {
    ?string $n2 = "y";
    $n2->upper();
} catch (\Error $e) {
    echo $e->getMessage(), "\n";
}
?>
--EXPECT--
Call to a member function trim() on string
Call to a member function foo() on true
Call to a member function trim() on string
Call to a member function upper() on string
