--TEST--
Scalar methods: the Str backing class is internal-only and not callable by name from userland
--FILE--
<?php
// The backing class for scalar string methods is registered under an internal-only,
// userland-unrepresentable name. It is the dispatch target of the `<string>->method()`
// desugar, but it is NOT reachable as a global `Str`: a direct `Str::trim(...)` call
// resolves no class named "Str" and errors. (Compare single.phpt, which shows the
// desugared `"..."->trim()` form working.)
try {
    var_dump(Str::trim("  x  "));
} catch (\Error $e) {
    echo get_class($e), ': ', $e->getMessage(), "\n";
}
try {
    var_dump(Str::length("hello"));
} catch (\Error $e) {
    echo get_class($e), ': ', $e->getMessage(), "\n";
}
?>
--EXPECT--
Error: Class "Str" not found
Error: Class "Str" not found
