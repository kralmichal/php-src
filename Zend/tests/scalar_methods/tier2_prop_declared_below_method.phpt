--TEST--
Scalar methods (Tier 2): string-typed property declared BELOW the method that uses it does NOT desugar (declaration-order soundness)
--FILE--
<?php
// When a `string`-typed property is declared textually AFTER the method that
// references it, the compiler has not yet seen the property declaration at the
// point it compiles the method body.  The method-call on the property must NOT
// be misrouted to Str:: -- it must fall through to the normal path and produce
// the standard "Call to a member function ... on string" error at runtime.
// This is a regression guard: nothing should silently desugar under-covered
// declaration-order cases.

class A {
    public function useIt() {
        // $this->greeting is a non-nullable string property, but its declaration
        // appears BELOW this method in the source file.
        try {
            return $this->greeting->trim();
        } catch (\Error $e) {
            echo $e->getMessage(), "\n";
        }
    }
    public string $greeting = "  hello  ";
}

// Sanity check: property declared ABOVE the method DOES desugar correctly.
class B {
    public string $greeting = "  hello  ";
    public function useIt() {
        return $this->greeting->trim();
    }
}

$a = new A();
$a->useIt();  // falls through, errors on string -- NOT desugared

$b = new B();
var_dump($b->useIt());  // correctly desugared: "hello"
?>
--EXPECT--
Call to a member function trim() on string
string(5) "hello"
