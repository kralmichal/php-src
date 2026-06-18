--TEST--
Typed local variables: $GLOBALS-unset of an aliased typed local does not crash at teardown
--DESCRIPTION--
A file-scope typed local that was aliased by a reference attaches its synthesized type as a
source on that reference. Unsetting it via the $GLOBALS superglobal (`unset($GLOBALS['x'])`)
compiles to ZEND_UNSET_VAR with ZEND_FETCH_GLOBAL and dtors the reference through the global
symbol table's IS_INDIRECT entry. The type source must be removed first, otherwise
zend_reference_destroy() asserts that the reference still carries a type source
(zend_variables.c). The owning frame is the script's main frame even when the unset is issued
from a nested function, so the clear walks the call chain. Must exit cleanly with and without
opcache (run under a debug build the assertion would otherwise abort).
--FILE--
<?php
// 1) Cross-frame: unset issued from inside a function; the typed local lives in main.
string $gs = 'a';
$r = &$gs;
function g() { unset($GLOBALS['gs']); }
g();
echo "cross-frame ok\n";

// 2) Same frame: unset at file scope.
string $hs = 'b';
$r2 = &$hs;
unset($GLOBALS['hs']);
echo "same-frame ok\n";

// 3) Deeply nested unset.
string $ds = 'c';
$r3 = &$ds;
function h() { (function () { unset($GLOBALS['ds']); })(); }
h();
echo "nested ok\n";

// 4) Multiple typed locals aliased; unsetting one via $GLOBALS leaves the other intact.
int $x1 = 1;
int $x2 = 2;
$ra = &$x1;
$rb = &$x2;
(function () { unset($GLOBALS['x1']); })();
var_dump($x2);
echo "multi ok\n";

// 5) The reference outlives the unset through the surviving alias. The unset typed local's
//    type source has been removed, so the now-unconstrained reference accepts any value
//    (the local that carried the type no longer exists), mirroring an unset typed property.
string $ks = 'k';
$rk = &$ks;
(function () { unset($GLOBALS['ks']); })();
$rk = [];
var_dump($rk);
echo "DONE\n";
?>
--EXPECT--
cross-frame ok
same-frame ok
nested ok
int(2)
multi ok
array(0) {
}
DONE
