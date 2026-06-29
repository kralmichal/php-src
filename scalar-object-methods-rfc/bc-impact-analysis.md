# BC impact analysis — scalar object methods (top-Packagist scan)

**Date:** 2026-06-29
**Branch:** `rfc/scalar-methods` @ `ed9601db49f` (primary) / `rfc/typed-locals` @ `125ed3965ab` (secondary)

Measures the real-world backward-compatibility impact of scalar object methods against a corpus of the most-popular Composer packages, in the style of prior PHP RFCs.

## What the feature can affect (the BC surface)

The desugar activates on exactly one thing: a method call whose receiver is a **compile-time-guaranteed scalar** (string/int/float literal, unary +/- over an int/float literal, `(string)`/`(int)`/`(float)` cast, concatenation, interpolation, a non-nullable scalar `$this` typed property, or a non-nullable-scalar-returning call). **Every such call site is a fatal error today** — you cannot call a method on a scalar without `Error: Call to a member function … on string/int/float`. So:

* No *currently-working* code can change behaviour; the only possible transition is **fatal → (dispatch, or a different "undefined method" error)**.
* New `Type $var = …` syntax is a parse error today, so no existing file contains it.
* No new reserved word is added (`int`/`float`/`string` were already reserved; `Str` is **not** reserved and the backing class is registered under a NUL-prefixed name, so userland `Str` classes are untouched).

The audit therefore measures: **how many guaranteed-scalar method-call sites exist in real code** (candidates for the fatal→dispatch transition), and **whether any userland class collides with a backing class**.

## Corpus

The **1,000 most-downloaded packages** on Packagist (resolved via the `popular.json` API), at latest stable. Includes the dominant ecosystem: `aws/aws-sdk-php`, `laravel/framework`, the Symfony components, `guzzlehttp/*`, `phpunit/phpunit`, the Doctrine packages, `monolog/monolog`, `nesbot/carbon`, `phpoffice/phpspreadsheet`, `phpseclib/phpseclib`, `nikic/php-parser`, `rector/rector`, `phpstan/phpstan`, etc. (998 scanned; 2 skipped on metadata/download error.)

* **173,110 PHP files** parsed (5 parse failures under the newest-PHP grammar — negligible; 1 entry >3 MB skipped).

## Method

To stay light on disk (no persisted corpus), each package's dist zip is streamed to a **single reused temp file**, its `.php` entries read **in memory** via `ZipArchive` (no extraction → no small-file writes), scanned, then the zip is deleted — pulling from `codeload.github.com` directly (the dist SHA) to avoid the GitHub API's 60/hour unauthenticated rate limit.

AST scan with nikic/PHP-Parser (`createForNewestSupportedVersion`). For every `MethodCall`/`NullsafeMethodCall` with a **literal** method name, the receiver expression is classified against the guaranteed-scalar forms above (the `$this`-typed-property case resolves the property's declared type within its class). Method names are matched (case-insensitively) against the proposed set (`trim/upper/lower/length/contains/startsWith/endsWith/abs/pow/clamp/round/ceil/floor`). The scanner was self-tested against a fixture containing one positive for each of the 13 receiver categories plus negatives (nullable property, untyped variable, out-of-set method) — all classified correctly — before running on the corpus.

*Limitation:* the scanner does not resolve the Tier-2 *scalar-returning call* receiver (`strlen($x)->m()`), which would require cross-function type inference. That form is also fatal today, so its real count is likewise expected to be ~0; the syntactically-detectable forms (which it does cover, and which are the bulk of the surface) came to zero.

## Results

| Metric | Count |
|---|---|
| Guaranteed-scalar method-call receivers (all categories) | **0** |
| Behaviour-change candidates (guaranteed-scalar receiver **and** method in the proposed set) | **0** |

**Zero affected call sites across 173,110 files.** This is the expected result — such sites are fatal errors today, so production code does not contain them — now confirmed empirically rather than asserted. (An earlier top-150 run, 36,224 files, gave the same zero.)

### Class-name collision (the "walled garden" / userland-`Str` concern)

`int`, `float`, and `string` are reserved class names, so no package can define `class Int`/`Float`/`String` (it is already a fatal). `str` is **not** reserved: a grep of a 150-package sample found **4 userland `class Str` declarations** — most notably **`Illuminate\Support\Str`** (Laravel; one of the most-used classes in PHP), plus `sentry/sentry`, `psy/psysh`, and a `guzzlehttp/psr7` test fixture.

None collide with the backing class. Verified empirically against the live build with both a namespaced (Laravel-style) and a global userland `Str`:

```php
namespace Illuminate\Support { class Str { static function trim($s){ return "LARAVEL-STR"; } } }
namespace {
    class Str { static function upper($s){ return "GLOBAL-STR"; } }
    var_dump("  hi  "->trim());                    // "hi"  — backing class, NOT "LARAVEL-STR"/"GLOBAL-STR"
    var_dump("hi"->upper());                        // "HI"  — backing class, NOT "GLOBAL-STR"
    var_dump(\Illuminate\Support\Str::trim("z"));   // "LARAVEL-STR" — userland class works normally
    var_dump(\Str::upper("z"));                     // "GLOBAL-STR"  — userland class works normally
}
```

The desugar dispatches to the internal NUL-named backing class; the userland `Str` classes remain fully functional under their own names. No collision, no breakage.

## Conclusion

In the top 1,000 Packagist packages (173k files), the feature has **zero** affected call sites, and the most widely-deployed userland `Str` class in the ecosystem (Laravel's) is unaffected. The BC impact is nil by construction (the feature only activates where code fatals today), and the scan confirms it in practice at scale.

## Reproduction

`/tmp/sm_audit/`: `top1000_scan.php` is the self-contained streaming scanner (reads `top1000.txt`, resolves each package's latest-stable dist via Packagist `p2` metadata, downloads the zip from `codeload.github.com` to one reused temp file, reads `.php` entries in memory via `ZipArchive`, AST-scans with PHP-Parser, deletes the zip). The `scan.php` variant scans an on-disk corpus root and carries the self-test fixture (`selftest/`) that validates all 13 receiver categories plus the negative cases. Scalable to larger N by raising the `array_slice` limit; the result is expected to remain 0 for the structural reason above.
