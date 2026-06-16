--TEST--
Scalar methods (Tier 2): receivers not provably non-nullable string do NOT desugar
--FILE--
<?php
// Each case below must fall through to the normal method-call path (and error as it
// does today on a non-object), NOT dispatch to Str::. We confirm by the error text
// "Call to a member function ... on <type>" (never "undefined method Str::...").
namespace {
    // (1) A `?string` (nullable) typed property is excluded: reading it yields null
    // here, and the normal path errors on null.
    class Nullable {
        public ?string $n = null;
        public function f() { return $this->n->trim(); }
    }
    try { (new Nullable)->f(); }
    catch (\Error $e) { echo $e->getMessage(), "\n"; }

    // (2) An `int` typed property is excluded: the normal path errors on the int.
    class IntProp {
        public int $n = 5;
        public function f() { return $this->n->trim(); }
    }
    try { (new IntProp)->f(); }
    catch (\Error $e) { echo $e->getMessage(), "\n"; }

    // (3) `$obj->prop` where $obj is an untyped local: we can't prove $obj's type, so
    // it is NOT desugared even though the property happens to be a string. The normal
    // path then errors on the string value at runtime.
    class HasString {
        public string $n = "x";
    }
    $o = new HasString();
    try { var_dump($o->n->trim()); }
    catch (\Error $e) { echo $e->getMessage(), "\n"; }

    // (4) A union `string|int` return is not guaranteed string: excluded.
    class UnionRet {
        public function m(): string|int { return "x "; }
        public function f() { return $this->m()->trim(); }
    }
    try { (new UnionRet)->f(); }
    catch (\Error $e) { echo $e->getMessage(), "\n"; }

    // (5) A method with no declared return type is excluded.
    class NoRet {
        public function m() { return "x "; }
        public function f() { return $this->m()->trim(); }
    }
    try { (new NoRet)->f(); }
    catch (\Error $e) { echo $e->getMessage(), "\n"; }

    // (6) A `?string`-returning method is excluded.
    class NullableRet {
        public function m(): ?string { return "x "; }
        public function f() { return $this->m()->trim(); }
    }
    try { (new NullableRet)->f(); }
    catch (\Error $e) { echo $e->getMessage(), "\n"; }
}

// (7) An UNqualified internal/global function call inside a namespace is ambiguous
// (it could bind to App\sprintf or fall back to the global \sprintf at runtime), so
// it is NOT compile-time-resolvable and must NOT desugar. The unqualified call still
// reaches the global sprintf at runtime, returns a string, and the normal ->trim()
// path then errors on that string value.
namespace App {
    class NsCall {
        public function f() {
            return sprintf("%s", " x ")->trim();
        }
    }
    try { (new NsCall)->f(); }
    catch (\Error $e) { echo $e->getMessage(), "\n"; }
}
?>
--EXPECT--
Call to a member function trim() on null
Call to a member function trim() on int
Call to a member function trim() on string
Call to a member function trim() on string
Call to a member function trim() on string
Call to a member function trim() on string
Call to a member function trim() on string
