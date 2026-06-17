--TEST--
Scalar methods (Tier 3): a variable-variable receiver ($$name) does NOT desugar
--FILE--
<?php
// `$$name` is a ZEND_AST_VAR whose name child is itself a ZEND_AST_VAR, not a
// literal ZVAL. The Tier-3 predicate only accepts a literal name child, so it
// returns false here -- even though `$$name` resolves at runtime to a real
// non-nullable `string` typed local. The call therefore runs as a normal dynamic
// method call on the string value (NOT Str::trim), confirming the exclusion is
// purely syntactic and never accidentally desugars indirect receivers.
$name = "s";
string $s = "hello";
try {
    $$name->trim();
} catch (\Error $e) {
    echo $e->getMessage(), "\n";
}
?>
--EXPECT--
Call to a member function trim() on string
