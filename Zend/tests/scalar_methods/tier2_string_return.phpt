--TEST--
Scalar methods (Tier 2): compile-time-resolvable :string-return receivers
--FILE--
<?php
// A call whose declared return type is exactly non-nullable `string` and that is
// resolvable at compile time is a string receiver: $this->m(), self::m(),
// static::m(), and a plain f() resolving to an internal function or to an
// already-declared (early-bound) user function. Each desugars to
// Str::method(<call-result>, ...).
namespace {
    function localName(): string { return "  local  "; }

    class C {
        public function name(): string { return "  bob  "; }
        public static function tag(): string { return "  X  "; }

        public function viaThis(): string {
            // $this->name() resolves to C::name():string; chain trim()->upper().
            return $this->name()->trim()->upper();
        }

        public function viaSelf(): string {
            return self::tag()->trim();
        }

        public function viaStatic(): string {
            return static::tag()->trim();
        }

        public function viaFunc(): string {
            // localName() is a top-level user function declared (early-bound) before
            // this class, so it is present and finalized in the function table at
            // compile time and resolves.
            return localName()->trim();
        }
    }

    var_dump((new C)->viaThis());
    var_dump((new C)->viaSelf());
    var_dump((new C)->viaStatic());
    var_dump((new C)->viaFunc());

    // static:: honours late static binding: an overriding method that (necessarily,
    // by return-type variance) also returns string is still a valid string receiver.
    class Base {
        public static function label(): string { return "  base  "; }
        public function go(): string { return static::label()->trim(); }
    }
    class Derived extends Base {
        public static function label(): string { return "  derived  "; }
    }
    var_dump((new Base)->go());
    var_dump((new Derived)->go());

    // A plain call to an internal function declared to return string is a receiver.
    var_dump(sprintf("%s", "  internal  ")->trim());
    var_dump(str_repeat("ab", 3)->upper());
}

// Inside a namespace: $this->n() (current class) and a FQ \sprintf() both resolve
// and desugar. (An UNqualified internal/global call inside a namespace is ambiguous
// — global fallback — and must NOT desugar; see tier2_excluded.phpt.)
namespace App {
    class N {
        public function n(): string { return "  ns  "; }
        public function f(): string {
            return $this->n()->trim() . "|" . \sprintf("%s", " q ")->trim();
        }
    }

    \var_dump((new N)->f());
}
?>
--EXPECT--
string(3) "BOB"
string(1) "X"
string(1) "X"
string(5) "local"
string(4) "base"
string(7) "derived"
string(8) "internal"
string(6) "ABABAB"
string(4) "ns|q"
