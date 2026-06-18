--TEST--
Typed local variables: by-name compound assign / inc-dec into an uninitialized typed local in strict mode (BP_VAR_RW)
--FILE--
<?php
declare(strict_types=1);

// Strict mode: a by-name compound add of an int into an uninitialized string-typed local
// throws (no weak coercion), matching the static "$s += 5" behaviour.
function strict_compound_rejects() {
    string $s;
    $n = 's';
    try {
        $$n += 5;
    } catch (\TypeError $e) {
        echo $e->getMessage(), "\n";
    }
    var_dump(isset($s));
}
echo "-- strict_compound_rejects --\n";
strict_compound_rejects();

// Strict mode: int into float on a fresh float-typed local widens (allowed even in strict),
// matching the static "$f += 2".
function strict_int_to_float_widens() {
    float $f;
    $n = 'f';
    $$n += 2;
    var_dump($f);
}
echo "-- strict_int_to_float_widens --\n";
strict_int_to_float_widens();

// Strict mode: increment on a fresh int local yields int(1).
function strict_inc() {
    int $u;
    $n = 'u';
    $$n++;
    var_dump($u);
}
echo "-- strict_inc --\n";
strict_inc();
?>
--EXPECTF--
-- strict_compound_rejects --

Warning: Undefined variable $s in %s on line %d
Cannot assign int to reference held by local variable $s of type string
bool(false)
-- strict_int_to_float_widens --

Warning: Undefined variable $f in %s on line %d
float(2)
-- strict_inc --

Warning: Undefined variable $u in %s on line %d
int(1)
