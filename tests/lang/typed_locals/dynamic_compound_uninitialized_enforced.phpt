--TEST--
Typed local variables: by-name compound assign / inc-dec into an UNINITIALIZED slot is type-checked (BP_VAR_RW)
--DESCRIPTION--
Companion to dynamic_write_uninitialized_enforced.phpt. That test closed the hole for a
by-name plain assign ($$name = ...) into a still-UNDEF typed local by promoting the CV
slot to a typed reference at the write, on the BP_VAR_W fetch path. Compound assignment
($$name .= ..., $$name += ...) and inc/dec ($$name++, --$$name) use the BP_VAR_RW fetch
path instead, which was not promoting -- so the first by-name compound/inc-dec write into
an uninitialized typed local landed in the raw slot unchecked, bypassing the declared
type.

The BP_VAR_RW fetch now NULL-initializes the still-UNDEF slot (as the static typed-CV RW
path does after the undefined-variable warning) and, for a typed CV, wraps that NULL in a
typed reference so the compound/inc-dec store routes through
zend_binary_assign_op_typed_ref()/zend_incdec_typed_ref() and is enforced. The observable
behaviour now matches the corresponding STATIC operation ($u .= ..., $u++) for the value /
TypeError outcome (the reference path phrases the TypeError as "reference held by local
variable", as it already does for the BP_VAR_W plain-assign path). Promotion happens only
on the write path, so a never-written typed local keeps undefined-variable semantics.
--FILE--
<?php
// Uninitialized typed local + by-name compound concat: non-coercible -> TypeError.
function compound_concat_checked() {
    int $u;
    $n = 'u';
    try {
        $$n .= "x";                  // string into int -> TypeError
    } catch (\TypeError $e) {
        echo $e->getMessage(), "\n";
    }
    var_dump(isset($u));             // failed write left it unset (null residue, isset=false)
}
echo "-- compound_concat_checked --\n";
compound_concat_checked();

// Uninitialized typed local + by-name compound add: weak-coerced like the static path.
function compound_add_coerced() {
    string $s;
    $n = 's';
    $$n += 5;                        // (null) + 5 = int(5), then coerced to "5" for string
    var_dump($s);
}
echo "-- compound_add_coerced --\n";
compound_add_coerced();

// Uninitialized typed local + by-name increment: int(1), matching $u++ on a fresh int.
function inc_checked() {
    int $u;
    $n = 'u';
    $$n++;
    var_dump($u);
}
echo "-- inc_checked --\n";
inc_checked();

// Uninitialized typed local + by-name pre-decrement: null can't go into int -> TypeError.
function predec_checked() {
    int $u;
    $n = 'u';
    try {
        --$$n;
    } catch (\TypeError $e) {
        echo $e->getMessage(), "\n";
    }
    var_dump(isset($u));
}
echo "-- predec_checked --\n";
predec_checked();

// Bitwise-or compound, uninitialized: null|5 -> int(5), accepted by int.
function bitor_checked() {
    int $u;
    $n = 'u';
    $$n |= 5;
    var_dump($u);
}
echo "-- bitor_checked --\n";
bitor_checked();

// An INITIALIZED typed local is still enforced on by-name compound (no regression).
function initialized_compound_checked() {
    int $u = 1;
    $n = 'u';
    try {
        $$n .= "x";
    } catch (\TypeError $e) {
        echo $e->getMessage(), "\n";
    }
    var_dump($u);                    // unchanged
    $$n += 2;                        // valid -> int(3)
    var_dump($u);
}
echo "-- initialized_compound_checked --\n";
initialized_compound_checked();

// Untyped local is unaffected: by-name compound is a plain unchecked operation.
function untyped_unchanged() {
    $u = 1;
    $n = 'u';
    $$n .= "x";                      // "1x"
    var_dump($u);
}
echo "-- untyped_unchanged --\n";
untyped_unchanged();

// Must-not-regress: a never-written typed local stays invisible to undefined-variable
// semantics (the compound/inc-dec write path is the only place promotion happens).
function undefined_semantics_preserved() {
    int $x;
    var_dump(isset($x));                 // false
    var_dump(get_defined_vars());        // empty
}
echo "-- undefined_semantics_preserved --\n";
undefined_semantics_preserved();
?>
--EXPECTF--
-- compound_concat_checked --

Warning: Undefined variable $u in %s on line %d
Cannot assign string to reference held by local variable $u of type int
bool(false)
-- compound_add_coerced --

Warning: Undefined variable $s in %s on line %d
string(1) "5"
-- inc_checked --

Warning: Undefined variable $u in %s on line %d
int(1)
-- predec_checked --

Warning: Undefined variable $u in %s on line %d

Warning: Decrement on type null has no effect, this will change in the next major version of PHP in %s on line %d
Cannot assign null to reference held by local variable $u of type int
bool(false)
-- bitor_checked --

Warning: Undefined variable $u in %s on line %d
int(5)
-- initialized_compound_checked --
Cannot assign string to reference held by local variable $u of type int
int(1)
int(3)
-- untyped_unchanged --
string(2) "1x"
-- undefined_semantics_preserved --
bool(false)
array(0) {
}
