--TEST--
Scalar methods: the backing class is internal-only and invisible to userland (no global Str)
--FILE--
<?php
// This file declares NO userland `Str`, so it observes the engine's default state:
// the scalar-method backing class exists internally (the desugar dispatches to it) but
// is registered under a userland-unrepresentable name and is invisible to userland.

// (1) Not visible by the name "Str".
var_dump(class_exists('Str'));
var_dump(in_array('Str', get_declared_classes(), true));

// (2) Not enumerated as a class of the standard extension.
var_dump(in_array('Str', (new ReflectionExtension('standard'))->getClassNames(), true));

// (3) Not reflectable by the name "Str".
try {
    new ReflectionClass('Str');
    echo "NO THROW\n";
} catch (\ReflectionException $e) {
    echo $e->getMessage(), "\n";
}

// (4) `Str::method()` is not callable from userland: no such class.
try {
    Str::trim("  x  ");
} catch (\Error $e) {
    echo get_class($e), ': ', $e->getMessage(), "\n";
}

// (5) Yet the desugared `<string>->method()` form still works, including chaining.
var_dump("  hi  "->trim());
var_dump("  hi  "->trim()->upper());
?>
--EXPECT--
bool(false)
bool(false)
bool(false)
Class "Str" does not exist
Error: Class "Str" not found
string(2) "hi"
string(2) "HI"
