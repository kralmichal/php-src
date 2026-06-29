# PHP RFC: Scalar object methods (draft)

* Version: 0.1 (draft)
* Date: 2026-06-16
* Status: Draft (pre-submission)
* Target: PHP 8.6 / next
* Branches: `rfc/scalar-methods` (primary), `rfc/typed-locals` (secondary, stacked)

> **Disclosure:** I built this with an AI assistant (Claude) as a tool. The design and the decisions are mine, and I have independently verified the engine behaviour, performance, JIT correctness, leak-freedom and the backward-compatibility scan. I am flagging it up front in the interest of transparency.

> One RFC, **two votes**: a primary vote on scalar methods (free receivers), and a separate secondary vote on extending receivers to scalar-typed local variables. The secondary part is structured so that a "no" on it cannot block the primary.

---

## Introduction

PHP exposes operations on scalar values as large, historically-shaped sets of free functions — `trim`, `strtoupper`, `strlen`, `str_replace`, … for strings; `abs`, `pow`, `intdiv`, … for integers — with inconsistent names, argument orders, and return conventions. This RFC lets you call a small, curated set of methods directly on a **scalar value**, dispatched by its type:

```php
echo "  Hello World  "->trim()->upper();   // string methods → "HELLO WORLD"
echo (-5)->abs();                          // int method → 5
echo (3.14159)->round(2);                  // float method → 3.14
echo "hello"->length()->pow(2);            // length()→int(5), chains into int → 25
echo ($first . $last)->upper();            // method on a concatenation
```

The feature is deliberately narrow: methods dispatch **only on values the compiler already knows are a given scalar type** — a guaranteed string dispatches to the string method set, a guaranteed int to the int set — the call is rewritten to an ordinary call **at compile time** (no new runtime dispatch, no new opcode), and the backing implementations are **not** new user-visible classes. There is no runtime type guessing and no loose-typing path — the central failure mode of every prior "methods on primitives" attempt.

The **primary** feature is a **capability**, not a coding-discipline proposal: it adds a way to *call* scalar operations and changes nothing about untyped code. The **secondary** feature — scalar-typed local variables — additionally makes the case for local type discipline (below); it is a separate vote precisely because that case is the more contested one, and a "no" on it cannot block the capability.

## Why compile-time, guaranteed receivers

The decade-old objection to scalar methods has been: "PHP is loosely typed, so `$x->trim()` would need a runtime type check, and behave differently depending on what `$x` holds." This RFC sidesteps that entirely by dispatching **only on receivers whose scalar type is guaranteed at compile time** — never on a value whose type is merely *inferred* by the optimizer (which would make behaviour depend on whether opcache is enabled).

This is, in fact, **npopov's own proposed resolution, systematized.** Facing exactly this loose-typing problem in 2014, he suggested that a mismatch should require an explicit cast — `((string) $num)->chunk()` — rather than implicit conversion. This RFC generalizes that principle to *any* compile-time-guaranteed scalar type: a literal, a `(T)` cast, a `:T`-return, or a declared typed local. The cast he proposed is then only needed where the type isn't already guaranteed — and the result is sound by construction, with no runtime type guessing.

A receiver is a *guaranteed scalar* if its type is one the compiler can prove syntactically. The forms below are written with string examples; the int and float forms are exactly analogous:

* a literal — `"..."->m()`, `(-5)->m()`, `(3.14)->m()` (a negative literal is unary minus over an int/float literal — literal notation, not arithmetic; float negation never overflows, and an int literal is in `[0, PHP_INT_MAX]` so it can't either)
* a `(string)` / `(binary)` / `(int)` / `(float)` cast — `((string) $x)->m()`, `((int) $x)->m()`, `((float) $x)->m()`
* a string concatenation — `($a . $b)->m()` (always a string)
* an interpolated string / heredoc — `"Hello $name"->m()`
* `$this->prop` where `prop` is a declared, non-nullable `string` / `int` / `float` typed property
* a **plain function** call declared to return a non-nullable scalar — an internal function (`strlen(...)`, `intdiv(...)`) or an already-declared, finalized user function (a userland *method* call result is **not** a receiver — see below)
* a chain of the above whose method returns a non-nullable scalar — including **across types**: `"x"->length()` is a guaranteed `int`, so it chains into the int methods

For any of these, the compiler rewrites `<recv>->method(args)` into a static call to the internal backing class **selected by the receiver's type** — `Str::` for a string, `Int::` for an int, `Float::` for a float — at compile time. An **untyped** variable receiver (`$x->trim()` where `$x` is a plain variable), an **arithmetic** receiver (`($a + $b)->m()`, optimizer-inferred, not declared), and a **bool** receiver (no backing class) are **not** rewritten and are left untouched: they produce the same `Error: Call to a member function … on <type>` they do today. Only what the compiler can prove is rewritten.

### A method call's *result* is not a receiver (but its look-alikes are)

The one boundary worth stating explicitly, because it is the first "what about…?" a reader reaches for: the **result of a userland method call is deliberately not a receiver**. What distinguishes the cases is *what kind of callable produced the value* — and three things that look like a method call are, in fact, receivers:

| Receiver expression | Desugars? | Why |
|---|---|---|
| `$this->name->trim()` — typed *property* read | **yes** | a declared non-nullable scalar property bypasses `__get`; the value is guaranteed its declared type |
| `strlen($x)->pow(2)` — *function*-call result | **yes** | functions cannot be overridden and unqualified-name fallback is rejected, so the resolved callee is the runtime callee |
| `"x"->trim()->upper()` — chain off a *backing* method | **yes** | `Str::trim` is a method on a final, internal class whose `: string` return the compiler reads directly — no inheritance, no variance question |
| `$this->getName()->trim()` — instance method result | **no** | excluded |
| `self::tag()->upper()` / `static::`/`parent::m()` — static method result | **no** | excluded |

The excluded forms (`$this->m()`, `self::`/`static::`/`parent::m()`) are left as ordinary method calls on a scalar — the same `Error: Call to a member function … on string` as today. They are excluded because their soundness would rest on **return-type covariance under inheritance and late static binding** (an override cannot *widen* a `: string` return, but relying on that across traits, `static::`, and statically-unknown scopes is a subtle argument for marginal value), and because it would be **`$this`-only**: `$this->getName()->trim()` could be proven while `$other->getName()->trim()` could not, an inconsistency not worth shipping. To chain off a method result, bind it to a typed local first: `string $n = $this->getName(); $n->trim();`.

## Proposal (primary vote)

### Method dispatch

In the compiler's method-call path, if the receiver expression is a guaranteed scalar (above), emit a static call to the backing class for that type (`Str` for a string, `Int` for an int) instead of `ZEND_INIT_METHOD_CALL`. Object, null, and unknown receivers are byte-for-byte unchanged — `ZEND_INIT_METHOD_CALL` and the entire object-call path are untouched, so there is **zero** regression risk for existing method calls and no new VM/JIT surface for them.

An undefined method on a guaranteed-scalar receiver (`"x"->nope()`, `(3)->nope()`) surfaces the engine's normal *"Call to undefined method …"* error through the static-call path.

### The backing methods are internal, not a user-visible class

To be precise about the framing: the methods *are* defined in real internal classes (one per scalar type — `Str`, `Int`, … — so the engine has somewhere to hold their implementations and signatures), but each is **registered under a reserved name that userland cannot reference** — the same mechanism anonymous classes use (the registered name's first byte is `NUL`). The class is **not representable as a userland identifier**: `class_exists('Str')` / `class_exists('Int')` are `false`, both are absent from `get_declared_classes()` and from `ReflectionExtension`/class enumeration, and a userland `class Str {}` does **not** collide with the backing class. The compiler rewrites `<recv>->method(args)` into a call into the appropriate internal class at compile time; there is no user-reachable `Str` / `Int` symbol by any normal name. (As with anonymous classes — which use the same NUL-prefixed convention — the internal name is *technically* reachable if a caller deliberately constructs the exact byte sequence, e.g. `class_exists("\0Str")` or `("\0Str")::trim(...)`; this is harmless — the methods are pure value functions — and we do not special-case the engine to forbid it, matching existing anonymous-class behaviour.) So the proposal introduces no new global name to bikeshed, reserve, or break — it is the single idea "methods on scalars," dispatched internally, not "a new `Str`/`Int` class."

(One small asymmetry worth stating: `int` and `float` are already reserved class names, so a userland `class Int {}` / `class Float {}` is *already* a fatal error today, independent of this RFC — whereas `Str` is **not** reserved (`str` ≠ `string`), so `class Str {}` is currently legal and stays legal. The no-collision guarantee holds for all three via the NUL-prefixed internal name; the difference is only that there is no pre-existing userland `Int`/`Float` class to protect in the first place, while a userland `Str` is protected.)

### Initial method sets

Small, curated, value-semantics (immutable / return-new) sets — one backing class per scalar type, selected by the receiver's compile-time type. A method earns a slot only by satisfying explicit selection criteria (pure / value-semantic; no by-reference or out-parameters; returns a single non-nullable scalar, an honest `int|float`/`bool`/`array`, never a `false` sentinel; common on the type; semantically unambiguous today). The full criteria, candidate tiers, and naming rationale are in the [companion method-set proposal](method-set-and-naming.md); the set below is the proposed **v1**.

**String** (string receivers) — proposed v1:

| Method | Returns | Backed by | Status |
|---|---|---|---|
| `trim(string $characters = " \n\r\t\v\0")` | `string` | `php_trim` | implemented |
| `upper()` | `string` | `php_string_toupper` | implemented |
| `lower()` | `string` | `php_string_tolower` | implemented |
| `length()` | `int` | `ZSTR_LEN` (bytes) | implemented |
| `contains(string $needle)` | `bool` | `php_memnstr` | implemented |
| `startsWith(string $prefix)` | `bool` | `zend_string_starts_with` | implemented |
| `endsWith(string $suffix)` | `bool` | `memcmp` | implemented |

**Int** (int receivers — the receiver is the implicit first operand) — proposed v1:

| Method | Returns | Backed by | Status |
|---|---|---|---|
| `abs()` | `int\|float` | core `abs()` (`abs(PHP_INT_MIN)` overflows to `float`, like the global `abs()`) | implemented |
| `pow(int $exponent)` | `int\|float` | `pow_function` | implemented |
| `clamp(int $min, int $max)` | `int` | `min`/`max` (throws if `$min > $max`) | implemented |

**Float** (float receivers) — proposed v1:

| Method | Returns | Backed by | Status |
|---|---|---|---|
| `round(int $precision = 0)` | `float` | `_php_math_round` | implemented |
| `ceil()` | `float` | libc `ceil` | implemented |
| `floor()` | `float` | libc `floor` | implemented |
| `abs()` | `float` | `fabs` | implemented |

**Bool** deliberately gets **no method set.** Its operations are language operators (`!`, `&&`, `||`), not value-methods; a `Bool` class would be padding for symmetry, not utility. The mechanism would accept a bool receiver — there is simply nothing worth putting there — so a `bool` receiver falls through unchanged. (A `bool` typed local is still valid; it just isn't a method receiver.) This is curation by genuine need, not forced generality.

The three sets demonstrate the mechanism generalizes across scalar types: `(3)->pow(2)`, `(-5)->abs()`, `(3.14)->round(2)` are free-receiver calls (no typed local needed), resolved at compile time exactly as the string calls are. Because a method's declared scalar return type makes its *result* a guaranteed scalar in turn, chains **compose across types** — `"hello"->length()` is a guaranteed `int`, so `"hello"->length()->pow(2)` dispatches `length()` to `Str` and `pow()` to `Int`. A result that is **not** a single non-nullable scalar is a **terminal**: `Int::abs`/`pow` return `int|float` (they overflow — `abs(PHP_INT_MIN)`, `(2)->pow(-1) === 0.5`) and the string predicates return `bool`, so none of those chain. The provably-single-scalar methods chain: `Str::trim/upper/lower` (`: string`), `Str::length` (`: int`), `Int::clamp` (`: int`), and every `Float` method (a float operation cannot overflow to another type, so `round/ceil/floor/abs` all stay `: float`). Declaring an overflowing method `: int` would be a lie the engine enforces — which is why `Int::abs`/`pow` are honestly `int|float`.

#### Open questions on the set — proposed positions (still open for discussion)

The mechanism is independent of the set (adding a method, a scalar type, or the `float`/`bool` sets is adding a backing class, not new syntax), so these are tunable without touching the design. The proposal takes a position on each; each remains open:

* **Byte vs. multibyte semantics — proposed: byte-oriented v1.** A PHP string is bytes, so `length()` returns bytes (`"café"->length()` is `5` in UTF-8) and `upper()`/`lower()` are ASCII/locale, matching the non-`mb_` functions they wrap. Multibyte-aware behaviour is named future scope, not silently chosen. *Open:* make the encoding-sensitive methods mb-aware from day one, or exclude them from v1. (This is the sharpest open question — `length()`/`upper()` are where it bites.)
* **Method-name casing — proposed: `camelCase`** for multi-word names (`startsWith`, not `starts_with`), matching modern class methods. *Open:* mirror the `snake_case` of the wrapped functions instead.
* **v1 size — proposed: the minimal set above** (the four implemented string methods + the canonical `contains`/`startsWith`/`endsWith` trio; `abs`/`pow`/`clamp` for int). Smaller = less surface in the first vote, and the set grows later with no syntax change. *Open:* a larger v1 — strong v1.x candidates already identified include `trimStart`/`trimEnd`, `replace`, `repeat`, `split`, `slice`, `indexOf` (the last must return `?int`, not a `false` sentinel).

## Proposal (secondary vote): scalar-typed local variables as receivers

The primary vote covers receivers the compiler can prove *syntactically*. The most natural case it cannot — calling a method on a value held in a local variable, `$s->trim()` — is enabled by giving the compiler a *declared* guarantee:

```php
string $s = $request->get('name');
echo $s->trim()->upper();          // desugars, compile-time, no per-call cast
```

This requires **scalar-typed local variables**: `Type $var = expr;` (with `?Type`), for the scalar types **`int`, `float`, `string`, `bool`** only. A non-nullable `string` typed local is then a guaranteed-string receiver, and `$s->trim()` is rewritten at compile time exactly like a literal — no runtime type check, no per-call `(string)` cast, no value dispatch. That is the optimization: the type declaration is what lets the compiler resolve the call statically.

### Why scalar-typed locals — two reasons, stated honestly

**1. Capability (any scalar receiver).** A non-nullable scalar typed local is a guaranteed receiver of its type, so the most natural form — methods on a value held in a variable — works, resolved at compile time, for every scalar the method sets cover:

```php
string $s = $request->get('name');
echo $s->trim()->lower()->replace('-', '_');   // IDE completion at each ->, no per-call (string) cast

int $f = 3;
echo $f->pow(2);                                // 9 — the int typed local is a guaranteed int receiver
```

The compelling case is **chains with discoverability**, not the single call: `$s->trim()->lower()->…` reads left-to-right with autocompletion at every `->`, versus nesting (`replace(lower(trim($s)), …)`) or piping through the global-function namespace. This is the direct extension of the primary feature to the receiver people actually want — it is the only way to chain methods *directly on a variable receiver* (`$s->trim()`), since an untyped variable cannot be proven and is left untouched; without it you must cast at each step (`((string) $s)->trim()`) or pipe through the function namespace.

**2. Local type discipline (all scalars).** Beyond receivers, declaring `int $count`, `float $ratio`, `string $name`, `bool $flag` makes a variable's contract explicit and *enforced* — it catches the coercion footguns that silently corrupt untyped locals (`int $id = $request->get('id')` rejects/normalizes a non-int instead of letting a numeric string flow on), and it completes a type system that already covers parameters, properties, and return types but not locals.

We state plainly that #2 is the contested claim — "types are less valuable locally than globally" (Ilija Tovilo). We argue it is worth it on two grounds a reviewer can check: it is **strictly opt-in** (zero cost, zero behaviour change for any code that doesn't write `Type $var`), and it is **consistency-completing** (locals are the one place the scalar type system stops). We are not routing around that debate by pretending int/float/bool exist only for method receivers — they don't; they exist for discipline, and we defend that. And because it is the contested half, it is a **separate vote**: the capability (primary) ships regardless of how the discipline argument lands.

### Semantics

* **Enforcement mirrors `declare(strict_types)` exactly**, identical to typed parameters and typed properties: weak mode coerces a coercible scalar (`string $s = 5` → `"5"`), strict mode throws on any mismatch. Non-coercible values throw in both modes.
* The type is a **true invariant**, enforced on **every** write: initialization, reassignment, compound assignment, `++`/`--`, dynamic by-name writes (`$$name`, `extract()`), and **all** reference paths (`$r = &$s`, by-ref parameters, `[&$s]`, `$o->p = &$s`, `C::$s = &$s`, `yield`, `use(&$s)`) — using the same `zend_reference` type-source machinery typed properties have used since 7.4. Taking a reference to an *uninitialized* typed local throws, exactly as for an uninitialized non-nullable typed property.
* Function-scoped; the type is bound at first appearance; using a variable before its typed declaration is a compile error.
* Persisted correctly through opcache (SHM and on-disk file_cache).
* *Implementation note:* a typed CV's declared type reuses the typed-property type machinery — a `zend_property_info` (only `type`/`name`/`flags` populated) per typed CV, plugged into the same `zend_reference` type-source list typed properties use. Reference enforcement, opcache persistence, and leak-safety are therefore the *same* validated code path, not a reimplementation. A dedicated CV-type carrier (dropping the unused property fields) is a reasonable follow-up; it would re-key the reference type-source list onto a new struct, so v1 stays on the proven holder.

### Scalar-only, and no `int|false`

Class, array, callable, union, and intersection types are out of scope. Nullable (`?int`) — the one legitimate "union" — is supported. `int|false` and similar sentinel unions are **deliberately not supported, on principle**: `int|false` is not a type you commit a variable to, it is the un-narrowed *sentinel state* before you have checked the `false` — an artifact of PHP's legacy failure-by-`false` convention, which the language is itself replacing with `?T` and exceptions. Typing a local straight off a sentinel-returning call is the bug (in weak mode it would silently coerce `false`→`0`, turning "not found" into "position 0"). So `?T` is in, sentinel-unions are out — a coherent boundary.

We do not claim this is free: it has a **real ergonomic cost**. You cannot write `string $line = fgets($fh);` (it returns `string|false`), and a large amount of stdlib still uses the `false`-sentinel convention — you must narrow first (`$line = fgets($fh); if ($line !== false) { string $confirmed = $line; … }`). This is a **deliberate, defensible restriction with a cost we accept**, not an oversight: blessing `int|false` as a committed local type would re-enable exactly the sentinel coercion above. Broader (class/array/union) typing is also out of scope — it has no method-receiver payoff and reopens the array/object element-type-check costs this RFC deliberately avoids.

## Backward incompatible changes

* `<guaranteed-scalar>->method(args)` (e.g. `"x"->trim()`, `(3)->pow(2)`) was previously a fatal *"Call to a member function … on string/int"*. It now dispatches to the backing method (or, for an unknown method, the normal undefined-method error). Method calls on **non**-guaranteed receivers (untyped variables, objects, null, bools, arithmetic results) are unchanged.
* No new global class name is introduced (the backing classes are internal-only), so existing userland `Str` classes are unaffected. (`Int` and `Float` are already reserved words, so no userland `Int`/`Float` class can exist to be affected.)
* `Type $var = …` is new syntax (secondary vote); it cannot break existing code.

**Real-world impact (measured).** An AST scan of the **1,000 most-downloaded Packagist packages** (173,110 PHP files — AWS SDK, Laravel, the Symfony components, Guzzle, PHPUnit, Doctrine, Monolog, Carbon, phpseclib, …) found **zero** method-call sites with a guaranteed-scalar receiver — i.e. **zero** call sites that change behaviour. This is the structural expectation (every such site is a fatal error today, so production code doesn't contain them), now confirmed empirically. The scan also found 4 packages defining a userland `class Str` — including Laravel's ubiquitous `Illuminate\Support\Str` — and verified all coexist with the NUL-named backing class (the desugar dispatches internally; the userland `Str` works normally by name). Full methodology and the reproducible scanner: `bc-impact-analysis.md`.

## Performance

The design keeps the untyped hot path untouched and confines all cost to opted-in code.

* **Untyped code is unchanged.** Plain `ZEND_ASSIGN` and all arithmetic/value-call opcodes are byte-for-byte identical; typed assignment uses *separate* opcodes (`ZEND_ASSIGN_TYPED`, …) emitted only for typed locals. Measured with deterministic callgrind instruction counts (baseline vs. branch): the pure arithmetic/assignment hot path differs by **+0.003%** (startup noise); the standard `Zend/bench.php` suite by **+0.145%**, all from predicted-not-taken branches in *reference* opcodes only, with **zero** added cache misses and **zero** added branch mispredictions (cache+branch simulation) — i.e. no measurable wall-clock cost.
* **The feature cost is favourable.** A typed-local write costs ~45 extra instructions (the type verification) — **0.79× the cost of a typed-*property* write**, which the language already ships and the community already accepted. Negligible on realistic code; visible only on assignment-saturated micro-loops.
* **Opcache:** validated under both SHM and on-disk file_cache; `cv_types` round-trips correctly; leak-free under stress.
* **JIT:** Untyped code is **fully JIT-accelerated and unaffected** — the typed-local opcodes are separate, so untyped SSA and codegen are byte-identical (an untyped hot loop JITs to the same ~6× speedup as before). Typed-local code is **correct under JIT in every mode** (tracing, function, and interpreter): this required modeling the typed-local opcodes in the optimizer's type-inference (so the JIT's SSA carries the right types) and enforcing typed-local references in the function-JIT codegen. Correctness is verified by a differential test requiring **JIT-off == function-JIT == tracing-JIT byte-identical** output across the full matrix of types, coercion, throwing, overflow, and every reference path. The remaining piece is purely a *speed* optimization: typed-local writes currently route through a runtime helper under JIT, so a typed-local *hot loop* runs at interpreter speed even with JIT on (untyped code is unaffected). Inlining the typed-local fast path in the JIT — so such loops reach untyped JIT speed — is a deliberate **follow-up**, the normal "new opcodes get JIT codegen incrementally" path; it is out of v1 scope and affects neither untyped JIT'd code nor correctness.

Full methodology and figures: `perf-validation.md`.

## Relationship to the pipe operator (`|>`)

PHP 8.5 added the pipe operator, which also chains operations on a value: `$s |> trim(...) |> strtoupper(...)`. This RFC is **complementary, not redundant**, and the difference is the axis that matters:

* **`|>` binds chains to the global-function namespace.** It pipes into `trim`, `str_replace`, `mb_*`, `strtoupper` — functions with their historical inconsistencies (argument order, naming, `needle`/`haystack` confusion, return conventions) and their full exposure to deprecation and signature change. A pipe chain is only as stable and discoverable as the functions it happens to name; the IDE can't complete "what can I pipe a string into" because the answer is "every function in the world."
* **`$s->method()` is a stable, curated API defined over the *type*.** A small, fixed, versioned method set the language owns — `$s->` autocompletes to exactly the operations defined for strings, the names and signatures are stable contracts (not subject to the churn the global namespace carries), and there is no dependency on the shape of the function namespace. This is the original motivation for the whole project: `trim()` the function is subject to API change; `$s->trim()` is defined over the type.

This curated surface is also a **clean slate** — npopov's *primary* argument for methods-on-primitives in 2014: *"a truly clean slate, without the need to meet any expectations coming with the old procedural API."* The value isn't cosmetic renaming; it's the chance to design the API *properly* — consistent names (`upper`/`lower`/`length`/`split`, not `strtoupper`/`strlen`/`str_split`), sane argument order, exceptions instead of `false`-sentinel returns — none of which the global functions can adopt without breaking BC, and all of which a `|>` chain inherits the lack of. The legacy namespace can never be cleaned up in place; a method set can be designed right from the start. (npopov noted internals contributors shared this preference.)

On **free receivers** especially, the honest differentiator is **curation and discoverability** (and this clean-slate design), *not* type-safety — `"foo"` is self-evidently a string, so "the type makes it sound" sells nothing there. The two features coexist cleanly: `|>` threads a value through arbitrary functions (including ones these methods will never cover); scalar methods give primitives a small, stable, discoverable, object-like surface for their most common operations. Neither obsoletes the other.

## Prior art and objections

This is the design space of npopov's 2014 "Methods on primitive types in PHP" and Wendell Adriel's abandoned "Types for Local Variables". The objections that sank prior attempts — and, in the last two rows, ones we anticipate here — with our answers (attributed where raised historically; the unattributed rows are anticipated):

| Objection (who) | Answer |
|---|---|
| Loose typing → `$x->trim()` needs a runtime check / behaves inconsistently | Dispatch only on **compile-time-guaranteed** scalars, never optimizer-inferred; identical with and without opcache; untyped variable receivers untouched. This *is* npopov's own 2014 resolution — require a cast, `((string)$x)->m()` — generalized to any guaranteed type (literal/cast/`:T`-return/typed local). |
| "A type check on every assignment → slower" (Rowan Tommins) | Separate `*_TYPED` opcodes; untyped hot path byte-for-byte identical (callgrind-verified). Typed-write cost < typed-property write. |
| "References are an unsolved problem" (Rowan) | Solved natively via the typed-property `zend_reference` source-list, enforced across every reference path, leak-verified under churn, opcache-safe. |
| `int $pos = strpos(...)` (`int\|false`) (Mark Baker) | `int\|false` is a sentinel state, not a committed type; the legitimate union `?int` is supported; sentinel-unions are declined on principle (see above). Overflow (`++` past `PHP_INT_MAX`) is handled and tested. |
| "Reassigning a typed *parameter* would BC-break" (Hans Henrik Bergan) | Moot — opt-in new syntax; typed params untouched. |
| "Types are much less valuable *locally* than globally" (Ilija Tovilo, #21317) | We engage the claim, not route around it (§"Why scalar-typed locals"). We **defend** local discipline on grounds a reviewer can check: it is strictly opt-in (zero cost / zero behaviour change unless you write `Type $var`), consistency-completing (locals are the one place the scalar type system stops), and it does catch real coercion footguns (`int $id = $request->get('id')`). Independently, this is structured so the debate need not block anything: the *primary* vote (scalar methods) needs no typed locals at all, and typed locals *also* enable the method-receiver capability — so a reviewer who rejects the discipline argument can still vote both the capability and the receivers in, on capability grounds alone. |
| "Userland getters aren't receivers (`$obj->getName()->trim()` fails), making the primary vote a Trojan horse for typed locals." | The votes are genuinely separable; the getter exclusion is for soundness (covariance/LSB), not strategy. The primary feature alone covers literals, casts, interpolation, typed properties, and function returns. For getters, the primary feature natively supports the cast escape hatch: `((string) $obj->getName())->trim()` — achieving the result without typed locals. |
| "Userland can't add methods to the backing class, creating an un-extendable walled garden." | Userland-extensible scalar methods lead directly to the monkey-patching ("behaves differently depending on what's loaded") disaster that PHP deliberately avoids. A curated, versioned, RFC-governed set is the stable alternative; specialist operations remain functions. |

## Future scope

* Larger curated method sets (string/int/float ship in v1; `bool` deliberately has none — its operations are operators, not methods). Growing a set is adding a backing-class method, not new syntax; method-set/naming is the main discussion item.
* Non-scalar receivers / element typing — explicitly out of scope (the expensive, contested case).
* JIT inlining of the typed-local fast path (if the in-progress codegen ships only the helper-call floor).
* A dedicated CV-type carrier instead of `zend_property_info` (drops the unused property fields, re-keys the reference type-source list) — a cleanup deferred to keep v1 on the proven enforcement path.

## Vote

* **Primary (2/3):** Add scalar methods on guaranteed-scalar receivers — **string, int and float** to start (free receivers + internal backing classes; `bool` deliberately has no set), as described.
* **Secondary (2/3, only if primary passes):** Additionally allow **scalar-typed local variables** (`Type $var`), enabling typed-local scalar receivers (`string $s = …; $s->trim()`, `int $f = …; $f->pow(2)`). A "no" here ships the primary feature without typed locals.
