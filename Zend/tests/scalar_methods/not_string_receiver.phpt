--TEST--
Scalar methods: non-concat binary ops are not strings and do not desugar
--FILE--
<?php
// `1 + 2` is a ZEND_AST_BINARY_OP, but its opcode is ZEND_ADD, not ZEND_CONCAT.
// Only concat yields a string, so arithmetic must NOT be treated as a string
// receiver: the call falls through to the normal method-call path and errors
// on the int result. This confirms we match the concat opcode specifically.
try {
    var_dump((1 + 2)->upper());
} catch (\Error $e) {
    echo $e->getMessage(), "\n";
}
// Subtraction likewise errors on an int.
try {
    (10 - 3)->trim();
} catch (\Error $e) {
    echo $e->getMessage(), "\n";
}
?>
--EXPECT--
Call to a member function upper() on int
Call to a member function trim() on int
