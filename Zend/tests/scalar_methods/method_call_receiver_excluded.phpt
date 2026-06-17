--TEST--
Scalar methods (Tier 2): method-call receivers ($this->m(), self::/static::m()) are NOT desugared
--FILE--
<?php
// A method-call *result* is deliberately not a guaranteed receiver: proving its type would rest
// on return-type covariance under inheritance / late static binding, an attack surface not worth
// the marginal value (and it would be $this-only, inconsistent with $obj->m()). So
// `$this->m()->trim()` is a normal method call on the string result and errors as
// "member function on string" -- it does not desugar to Str::trim. (A $this typed-property read,
// `$this->prop->trim()`, IS a receiver -- see tier2_typed_property.phpt.)
class C {
    public function name(): string { return "bob"; }
    public static function tag(): string { return "x"; }
    public function viaThis()   { return $this->name()->trim(); }
    public function viaSelf()   { return self::tag()->trim(); }
    public function viaStatic() { return static::tag()->trim(); }
}
foreach (['viaThis', 'viaSelf', 'viaStatic'] as $m) {
    try { (new C)->$m(); echo "$m: NO ERROR (desugared?!)\n"; }
    catch (\Error $e) { echo "$m: ", $e->getMessage(), "\n"; }
}
?>
--EXPECT--
viaThis: Call to a member function trim() on string
viaSelf: Call to a member function trim() on string
viaStatic: Call to a member function trim() on string
