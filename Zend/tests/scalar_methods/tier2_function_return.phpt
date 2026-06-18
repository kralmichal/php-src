--TEST--
Scalar methods (Tier 2): a plain function call with a non-nullable scalar return type is a receiver
--FILE--
<?php
// A plain f() call whose declared return type is exactly non-nullable string/int and that
// resolves at compile time -- an internal function (always registered), or an already-declared,
// finalized user function -- is a guaranteed receiver and desugars to Str::/Int::method(f(), ...).
// Method-call results ($this->m(), self::/static::m()) are deliberately NOT receivers; see
// method_call_receiver_excluded.phpt.
namespace {
    function localName(): string { return "  local  "; }

    var_dump(localName()->trim());                    // user function : string -> Str::trim
    var_dump(sprintf("%s", "  internal  ")->trim());  // internal : string     -> Str::trim
    var_dump(str_repeat("ab", 3)->upper());           // internal : string     -> Str::upper
    var_dump(strlen("hello")->pow(2));                // strlen : int -> Int::pow (cross-type)
}

namespace App {
    // A fully-qualified internal call inside a namespace resolves and desugars. (An UNqualified
    // internal/global call inside a namespace is ambiguous -- global fallback -- and must NOT
    // desugar; see tier2_excluded.phpt.)
    \var_dump(\sprintf("%s", "  q  ")->trim());
}
?>
--EXPECT--
string(5) "local"
string(8) "internal"
string(6) "ABABAB"
int(25)
string(1) "q"
