--TEST--
Typed local variables: first by-name write into an UNINITIALIZED slot is type-checked (D-1 closed)
--DESCRIPTION--
By-name dynamic writes ($$name, extract(), ...) into a typed local are enforced by
promoting the CV slot to a typed reference, whose synthesized type source routes the
write through zend_assign_to_typed_ref()/zend_verify_ref_assignable_zval(). Previously
this promotion only happened for slots that already held a value (it ran at symbol-table
materialization and skipped IS_UNDEF), so the FIRST by-name write into a declared-but-
unassigned typed local landed in the raw slot unchecked.

That hole is now closed by promoting at the WRITE instead of at materialization: the
dynamic-variable write fetch (zend_fetch_var_address_helper, BP_VAR_W) and extract()'s
overwrite/initialize paths promote a still-UNDEF typed CV to a typed reference just
before the assign, so the very first by-name write is enforced. Because promotion only
happens on the write path, a read / isset() / get_defined_vars() that observes the slot
before any write still sees a bare IS_UNDEF and undefined-variable semantics are
unchanged (verified below). The opcache CV-compaction pass keeps typed locals (and their
parallel cv_types[]) so the type survives optimization for by-name-only locals too.
--FILE--
<?php
// First by-name write into an uninitialized typed local is now CHECKED.
function first_write_checked() {
    int $u;
    $n = 'u';
    try {
        $$n = "not-an-int";          // non-coercible -> TypeError, slot stays unset
    } catch (\TypeError $e) {
        echo $e->getMessage(), "\n";
    }
    var_dump(isset($u));             // still unset: the failed write did not initialize it
}
echo "-- first_write_checked --\n";
first_write_checked();

// Weak mode coerces a coercible value on the first by-name write.
function first_write_coerced() {
    int $u;
    $n = 'u';
    $$n = "5";                       // coerced to int(5)
    var_dump($u);
}
echo "-- first_write_coerced --\n";
first_write_coerced();

// Subsequent by-name writes remain checked.
function later_writes_checked() {
    int $u;
    $n = 'u';
    $$n = "5";                       // 1st: coerced
    var_dump($u);
    try {
        $$n = "still-not-an-int";    // 2nd: checked -> TypeError, value unchanged
    } catch (\TypeError $e) {
        echo $e->getMessage(), "\n";
    }
    var_dump($u);
}
echo "-- later_writes_checked --\n";
later_writes_checked();

// extract() onto an uninitialized typed local: the first write is checked too.
function extract_first_checked() {
    int $u;
    try {
        extract(['u' => [1, 2]]);    // array is not assignable to int -> TypeError
    } catch (\TypeError $e) {
        echo $e->getMessage(), "\n";
    }
    var_dump(isset($u));
    extract(['u' => "7"]);           // coercible -> int(7)
    var_dump($u);
}
echo "-- extract_first_checked --\n";
extract_first_checked();

// An INITIALIZED typed local is still enforced on its first by-name write.
function initialized_is_checked() {
    int $u = 0;
    $n = 'u';
    try {
        $$n = "not-an-int";
    } catch (\TypeError $e) {
        echo $e->getMessage(), "\n";
    }
    var_dump($u);
}
echo "-- initialized_is_checked --\n";
initialized_is_checked();

// Must-not-regress: a never-written uninitialized typed local stays invisible to
// undefined-variable semantics even after the symbol table is built (the write path is
// the only place promotion happens, so an unwritten slot is never turned into a ref).
function undefined_semantics_preserved() {
    int $x;
    var_dump(isset($x));                 // false: still undefined
    var_dump(get_defined_vars());        // empty: not reported
    var_dump(count(get_defined_vars())); // 0
}
echo "-- undefined_semantics_preserved --\n";
undefined_semantics_preserved();
?>
--EXPECTF--
-- first_write_checked --
Cannot assign string to reference held by local variable $u of type int
bool(false)
-- first_write_coerced --
int(5)
-- later_writes_checked --
int(5)
Cannot assign string to reference held by local variable $u of type int
int(5)
-- extract_first_checked --
Cannot assign array to reference held by local variable $u of type int
bool(false)
int(7)
-- initialized_is_checked --
Cannot assign string to reference held by local variable $u of type int
int(0)
-- undefined_semantics_preserved --
bool(false)
array(0) {
}
int(0)
