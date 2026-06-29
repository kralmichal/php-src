--TEST--
Scalar methods (string): contains/startsWith/endsWith dispatch and return bool (terminal)
--FILE--
<?php
// The 8.0 str_* predicate family as string methods. The receiver is the haystack (so there is
// no needle/haystack argument-order ambiguity). All return bool, so they are terminals.
var_dump("hello world"->contains("wor"));   // true
var_dump("hello"->contains("xyz"));         // false
var_dump("hello"->contains(""));            // true (empty needle)
var_dump("hello"->startsWith("he"));        // true
var_dump("hello"->startsWith("lo"));        // false
var_dump("hello"->endsWith("lo"));          // true
var_dump("hi"->endsWith("longer"));         // false (suffix longer than subject)

// They work on any guaranteed-string receiver, e.g. a concatenation.
var_dump(("foo" . "bar")->endsWith("bar")); // true

// bool result is a terminal: chaining a method onto it errors normally (the engine reports a
// bool value as "true"/"false" in the message).
try { "hello"->contains("h")->whatever(); } catch (\Error $e) { echo $e->getMessage(), "\n"; }
?>
--EXPECT--
bool(true)
bool(false)
bool(true)
bool(true)
bool(false)
bool(true)
bool(false)
bool(true)
Call to a member function whatever() on true
