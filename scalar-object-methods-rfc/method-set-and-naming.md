# Scalar method sets & naming — proposal (for the RFC's "method set" discussion)

**Status:** draft for review. This is the artifact for the question most likely to come back from internals: *which methods, and what names?* The defense is the **selection criteria**, not the list — the criteria are what answer "why isn't X in here?" and "why is Y in here?" without re-bikeshedding each name.

**What's implemented** (on `rfc/scalar-methods`): the full proposed v1 — `Str` (trim/upper/lower/length/contains/startsWith/endsWith), `Int` (abs/pow/clamp), `Float` (round/ceil/floor/abs); `bool` deliberately has no set. Rows marked ✓ below are built. The mechanism is independent of the set: adding a method is adding a backing-class method, no syntax change.

---

## Selection criteria (the principled core)

A method earns a slot only if it satisfies **all** of these. State these first in the RFC; defend the list with them.

1. **Pure, value-semantic.** Takes the receiver by value, returns a new value. No mutation, no side effects. (Rules out anything that writes through a reference.)
2. **No by-reference / out-parameters.** Methods that need to *write back* (`sscanf`, `preg_match($s, $m)`, `str_replace($s, $r, $subj, $count)`) stay as functions. This is a hard line: out-params don't fit a value-method model, and it cleanly answers "why isn't `sscanf` a method."
3. **Return type is a single non-nullable scalar, an honest `int|float`, `bool`, or `array`.** A method that *can* fail returns a typed/throwing result, **never a `false` sentinel** (see naming principle 4). Methods returning a single non-nullable scalar are *chainable*; `int|float`/`bool`/`array` returns are *terminals* (they don't compose further — by design, stated honestly).
4. **Common enough to belong on the type's surface.** The bar is "a typical program uses this on this type often." Niche/specialist operations stay in the function namespace or an extension.
5. **Semantics unambiguous for the type as it exists today.** A PHP string is a byte string; a method whose meaning depends on encoding (character count, Unicode case) is deferred to an explicit mb-aware future tier rather than silently picking bytes-or-chars (see Open Question 1).

A useful **structural** consequence to highlight in the RFC: because the receiver *is* the subject, the needle/haystack argument-order confusion that plagues the function namespace (`in_array($needle, $haystack)` vs `strpos($haystack, $needle)`) **disappears by construction** — the haystack is always `$this`, the needle is always the argument.

---

## Proposed v1 — `Str` (byte-oriented)

Deliberately tight. Each row notes what it wraps and whether it chains.

**Locked proposal: the minimal 7** (the four implemented transforms + the canonical search trio). Each satisfies all five criteria; the only encoding ambiguity is in `upper`/`lower`/`length` (Open Question 1).

| Method | Signature | Wraps | Result |
|---|---|---|---|
| `trim` ✓ | `trim(string $characters = " \n\r\t\v\0"): string` | `php_trim` (both) | chainable (string) |
| `upper` ✓ | `upper(): string` | `strtoupper` | chainable |
| `lower` ✓ | `lower(): string` | `strtolower` | chainable |
| `length` ✓ | `length(): int` | `ZSTR_LEN` (bytes) | chainable (int → int methods) |
| `contains` ✓ | `contains(string $needle): bool` | `php_memnstr` | terminal (bool) |
| `startsWith` ✓ | `startsWith(string $prefix): bool` | `zend_string_starts_with` | terminal (bool) |
| `endsWith` ✓ | `endsWith(string $suffix): bool` | `memcmp` | terminal (bool) |

The `contains`/`startsWith`/`endsWith` trio is high-value: the canonical 8.0 `str_*` family, already bool-returning and byte-clean, and it shows the set is more than string→string transforms. Locking v1 here keeps the first vote's surface small; the set grows by adding backing methods later with **no syntax change**.

**Strong v1.x candidates** (deferred to keep v1 minimal — Open Question 3 is whether to pull any into v1):
- `trimStart` / `trimEnd` (`ltrim`/`rtrim`) — clean, byte-safe, chainable; the most likely to be promoted.
- `replace(string $search, string $replace): string` (scalar `str_replace`), `repeat(int $times): string` (`str_repeat`) — chainable, byte-safe.
- `split(string $separator, int $limit = PHP_INT_MAX): array` (`explode`) — terminal (array).
- `slice`/`substr` (negative-offset semantics to pin down), `padStart`/`padEnd` (`str_pad`), `reverse` (`strrev`).
- `indexOf` — must return `?int` (or throw), **never** `strpos`'s `int|false` sentinel (principle 4); the sentinel resolution is exactly why it's deferred, not dropped.

## Proposed v1 — `Int`

Honestly thin — int has few natural pure methods. This set is more "prove the mechanism generalizes beyond string" than a rich API, and the RFC should say so plainly rather than pad it.

| Method | Signature | Wraps | Result |
|---|---|---|---|
| `abs` ✓ | `abs(): int\|float` | core `abs` | terminal (overflows at `PHP_INT_MIN`) |
| `pow` ✓ | `pow(int $exponent): int\|float` | `pow` | terminal (overflows / negative exp → float) |
| `clamp` ✓ | `clamp(int $min, int $max): int` | `min`+`max` (throws if `$min > $max`) | **chainable** (the one provably-`:int` int method) |

`clamp` earns its slot because it's the only candidate provably `: int` for all inputs (so it actually chains), it's genuinely common, and it gives the int set a non-overflowing member. `gcd`, `toBase(): string`, etc. are future scope.

## Proposed v1 — `Float`

Unlike Int, a float operation cannot overflow to another type, so **every** Float method returns exactly `float` and is therefore chainable.

| Method | Signature | Wraps | Result |
|---|---|---|---|
| `round` ✓ | `round(int $precision = 0): float` | `_php_math_round` (half-up) | chainable |
| `ceil` ✓ | `ceil(): float` | libc `ceil` | chainable |
| `floor` ✓ | `floor(): float` | libc `floor` | chainable |
| `abs` ✓ | `abs(): float` | `fabs` | chainable |

## `Bool` — deliberately no method set

Bool gets **no backing class**, on purpose. Its operations are language operators (`!`, `&&`, `||`), not value-methods; the only "methods" one could invent (`not()`, etc.) duplicate operators and would be padding for symmetry. The desugar mechanism is type-agnostic and *would* accept a bool receiver — there is simply nothing worth dispatching to — so a bool receiver falls through unchanged. A `bool` typed local remains valid (it's a type for variables); it just isn't a method receiver. Shipping no Bool class is the disciplined choice: the sets are curated by genuine utility, not completed for the sake of covering all four scalar types.

---

## Naming principles (the "clean slate")

This is Nikita's primary 2014 argument — the value is designing the API *right*, freed from the procedural names. State these as rules:

1. **No type prefix.** `upper`, not `strtoupper`; `length`, not `strlen`; `repeat`, not `str_repeat`. The type is the receiver.
2. **Say what it does, not how it was implemented.** `contains`, not `str_contains`; `split`, not `explode`.
3. **`camelCase` for multi-word method names** (`startsWith`, `trimStart`) — the convention for methods on modern PHP classes (`DateTime::createFromFormat`). *(Open Question 2 — internals may prefer to mirror the `snake_case` of the wrapped functions.)*
4. **No `false` sentinels — ever.** A method that can "not find"/"fail" returns a typed result or throws, never `false`. This is why `indexOf` is held: it must return `?int` (or throw), not `strpos`'s `int|false`. Aligning with the language's own move away from failure-by-`false`.
5. **Receiver is the subject.** Haystack/needle order is fixed by construction (haystack = `$this`); no argument-order ambiguity to design around.

---

## Open questions — locked proposal positions, still open for discussion

The RFC takes a definite position on each of these (so it isn't "TBD"), but each remains genuinely open — the mechanism is independent of every one, so any can change without touching the design. Stated as: **proposed answer**, then what's on the table.

**1. Byte semantics vs. multibyte — the sharpest one.** A PHP string is bytes. `length()` returns *bytes* (`"café"->length()` is 5 in UTF-8, not 4); `upper()`/`lower()` are ASCII/locale, so `"café"->upper()` is `"CAFé"`.
   - **Proposed: byte-oriented v1**, matching the non-`mb_` functions it wraps; mb-aware behaviour is explicit named future scope. Rationale: no encoding parameter to bikeshed, exact parity with the wrapped functions, smallest honest v1.
   - *Still open:* make the methods mb-aware from day one (correctness-first, but pulls in `ext/mbstring` semantics, an encoding question, and perf cost), or exclude the encoding-sensitive methods (`length`/`upper`/`lower`) from v1.
   - This is the objection most likely from Unicode-conscious reviewers; expect pushback on `length()` specifically. The position is deliberately the conservative one so the discussion can *widen* it rather than walk back a too-broad claim.

**2. Method-name casing.**
   - **Proposed: `camelCase`** for multi-word names (`startsWith`, `trimStart`) — the convention for methods on modern PHP classes.
   - *Still open:* mirror the `snake_case` of the wrapped functions (`starts_with`). Either way, apply it uniformly.

**3. v1 size.**
   - **Proposed: the minimal sets** — 7 `Str` (the 4 transforms + the `contains`/`startsWith`/`endsWith` trio), 3 `Int` (`abs`/`pow`/`clamp`), 4 `Float` (`round`/`ceil`/`floor`/`abs`); `bool` deliberately none. Smallest surface for the first vote; the sets grow later with no syntax change.
   - *Still open:* a larger v1 pulling in the strong v1.x candidates above (most likely `trimStart`/`trimEnd`, then `replace`/`repeat`/`split`).

---

## What this buys the RFC

Walking in with criteria + a locked-but-tunable set + the open questions *already framed with a position* converts "method set is TBD" (an easy reason to defer) into "here's a considered set, a default answer on each open decision, and the room to move it" — and pre-answers the "why not X / why Y" reflex with rules instead of taste.
