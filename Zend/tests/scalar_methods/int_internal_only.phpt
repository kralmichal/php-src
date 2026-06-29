--TEST--
Scalar methods (int): the Int backing class is internal-only and invisible to userland
--FILE--
<?php
// The scalar-method int backing class exists internally (the desugar dispatches to it) but
// is registered under a userland-unrepresentable NUL-prefixed name and is invisible to
// userland. Note: unlike "Str", the bare name "Int" is also a reserved type keyword in PHP
// (case-insensitive "int"), so userland could not declare `class Int {}` regardless — but
// that is independent of the hiding mechanism tested here.

// (1) Not visible by the name "Int".
var_dump(class_exists('Int'));
var_dump(in_array('Int', get_declared_classes(), true));

// (2) Not enumerated as a class of the standard extension.
var_dump(in_array('Int', (new ReflectionExtension('standard'))->getClassNames(), true));

// (3) Not reflectable by the name "Int".
try {
    new ReflectionClass('Int');
    echo "NO THROW\n";
} catch (\ReflectionException $e) {
    echo $e->getMessage(), "\n";
}

// (4) `Int::method()` is not callable from userland: no such class.
try {
    \Int::pow(2, 3);
} catch (\Error $e) {
    echo get_class($e), ': ', $e->getMessage(), "\n";
}

// (5) Yet the desugared `<int>->method()` form still works (abs is a terminal: not chained).
var_dump((3)->pow(2));
var_dump((2)->abs());
?>
--EXPECT--
bool(false)
bool(false)
bool(false)
Class "Int" does not exist
Error: Class "Int" not found
int(9)
int(2)
