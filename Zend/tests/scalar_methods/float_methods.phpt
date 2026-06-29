--TEST--
Scalar methods (float): guaranteed-float receivers dispatch to the Float backing class
--FILE--
<?php
// A guaranteed float receiver -- a float literal (incl. unary minus), a (float)/(double) cast,
// a float-returning call, or a non-nullable float typed property -- desugars to Float::method().
// Every Float method returns float (a float op cannot overflow to another type), so all chain.

// Float literal receivers.
var_dump((3.14159)->round(2));   // 3.14
var_dump((3.2)->ceil());         // 4.0
var_dump((3.8)->floor());        // 3.0
var_dump((-2.5)->abs());         // 2.5 -- unary-minus float literal is a guaranteed float

// (float) cast receivers (a (double) cast has attr IS_DOUBLE too and desugars identically,
// but the (double) spelling is deprecated, so we use (float) here).
var_dump(((float) "2.7")->floor());   // 2.0
var_dump(((float) 5)->abs());         // 5.0

// Chaining: every Float method returns float, so they compose.
var_dump((-2.7)->abs()->ceil());      // 3.0
var_dump((1.23456)->round(2)->ceil()); // round->1.23, ceil->2.0

// The backing class is internal-only.
var_dump(class_exists('Float'));      // false

// An unknown method on a float receiver errors as undefined on the (NUL-named) backing class.
try { (1.5)->nope(); } catch (\Error $e) { echo $e->getMessage(), "\n"; }

// Exclusions: an untyped variable holding a float is not guaranteed; arithmetic is inferred.
try { $f = 1.5; $f->abs(); } catch (\Error $e) { echo $e->getMessage(), "\n"; }
try { (1.0 + 0.5)->abs(); } catch (\Error $e) { echo $e->getMessage(), "\n"; }
?>
--EXPECT--
float(3.14)
float(4)
float(3)
float(2.5)
float(2)
float(5)
float(3)
float(2)
bool(false)
Call to undefined method ::nope()
Call to a member function abs() on float
Call to a member function abs() on float
