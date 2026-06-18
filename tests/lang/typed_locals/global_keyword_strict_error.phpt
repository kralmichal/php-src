--TEST--
Typed local variables: under strict_types a coercible string imported via `global` still throws
--FILE--
<?php
declare(strict_types=1);

// In strict mode a typed reference rejects even a coercible scalar, matching the static
// ($g = "5") and $GLOBALS paths: the imported global must throw, not coerce.

int $g = 1;

function set_coercible() {
    global $g;
    try {
        $g = "5";
    } catch (\TypeError $e) {
        echo $e->getMessage(), "\n";
    }
}
set_coercible();
var_dump($g);

// a valid same-type write still works in strict mode
function set_valid() { global $g; $g = 42; }
set_valid();
var_dump($g);
?>
--EXPECT--
Cannot assign string to reference held by local variable $g of type int
int(1)
int(42)
