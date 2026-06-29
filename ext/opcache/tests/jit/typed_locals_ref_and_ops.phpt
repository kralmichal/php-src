--TEST--
JIT: typed-local references AND *_TYPED ops enforce + compute correctly under jit=function and jit=tracing
--INI--
opcache.enable=1
opcache.enable_cli=1
opcache.file_update_protection=0
--EXTENSIONS--
opcache
--FILE--
<?php
/* Guards the function-JIT typed-local reference fix and the inc/dec-through-typed-ref
 * helper fix. The worker exercises typed-local reference creation (alias, array literal,
 * closure capture, by-ref parameter), forbids referencing an uninitialized typed local
 * through every creation path, runs the *_TYPED opcodes in a hot (JIT-compiled) loop, and
 * increments/decrements past the int bounds THROUGH a typed-local reference. It runs under
 * the interpreter and both JIT modes; all three outputs must be byte-identical and every
 * enforcement must fire. Before the fix, function-JIT silently dropped the type source on
 * inline reference creation (wrong value / no throw), crashed at frame teardown (the
 * unrolled CV-free skipped the type-source removal), and segfaulted on inc/dec overflow
 * through a typed-local reference (the JIT error helper assumed a class-property source). */

$worker = <<<'PHP'
<?php
function t(callable $f) {
    try { $f(); }
    catch (\TypeError $e) { echo "TE: ", $e->getMessage(), "\n"; }
    catch (\Error $e)     { echo "E: ", $e->getMessage(), "\n"; }
}
function setRef(&$r, $v) { $r = $v; }
function f_alias()   { int $a = 1; $r = &$a; t(function() use (&$r) { $r = "x"; }); var_dump($a); $r = "5"; var_dump($a); }
function f_array()   { int $a = 5; $arr = [&$a]; t(function() use (&$arr) { $arr[0] = "x"; }); var_dump($a); $arr[0] = "9"; var_dump($a); }
function f_closure() { int $a = 5; $set = function($v) use (&$a) { $a = $v; }; t(function() use ($set) { $set("x"); }); var_dump($a); $set("9"); var_dump($a); }
function f_byref()   { int $a = 1; setRef($a, "5"); var_dump($a); }
function u_alias()   { int $a; $r = &$a; }
function u_arr()     { int $a; $x = [&$a]; }
function u_obj()     { int $a; $o = new stdClass; $o->p = &$a; }
function u_static()  { int $a; H2::$s = &$a; }
function u_closure() { int $a; $c = function() use (&$a) {}; }
function u_byref()   { int $a; setRef($a, 1); }
class H2 { public static $s; }
function f_ops() {
    int $i = 0; float $f = 0.0; string $s = "";
    for ($n = 0; $n < 3000; $n++) { $i++; $i += 2; $f += 0.5; if ($n % 1000 === 0) $s .= "z"; }
    var_dump($i, $f, $s);
    int $m = PHP_INT_MAX; $r = &$m;  t(function() use (&$r)  { $r++; });  var_dump($m);
    int $k = PHP_INT_MIN; $rk = &$k; t(function() use (&$rk) { --$rk; }); var_dump($k);
}
f_alias(); f_array(); f_closure(); f_byref();
t('u_alias'); t('u_arr'); t('u_obj'); t('u_static'); t('u_closure'); t('u_byref');
f_ops();
echo "OK\n";
PHP;

$file = __DIR__ . '/typed_locals_ref_and_ops_worker.php';
file_put_contents($file, $worker);

$php = getenv('TEST_PHP_EXECUTABLE');
$base = ' -d opcache.enable=1 -d opcache.enable_cli=1 -d opcache.jit_buffer_size=64M ';

function run(string $php, string $args, string $file): string {
    $cmd = escapeshellarg($php) . ' ' . $args . ' ' . escapeshellarg($file) . ' 2>&1';
    return shell_exec($cmd);
}

$off  = run($php, $base . '-d opcache.jit=0',        $file);
$func = run($php, $base . '-d opcache.jit=function', $file);
$trac = run($php, $base . '-d opcache.jit=tracing',  $file);

@unlink($file);

if ($off === $func && $off === $trac) {
    echo "ALL THREE IDENTICAL\n";
    echo $off;
} else {
    echo "MISMATCH\n";
    echo "--- off ---\n", $off;
    echo "--- function ---\n", $func;
    echo "--- tracing ---\n", $trac;
}
?>
--EXPECT--
ALL THREE IDENTICAL
TE: Cannot assign string to reference held by local variable $a of type int
int(1)
int(5)
TE: Cannot assign string to reference held by local variable $a of type int
int(5)
int(9)
TE: Cannot assign string to reference held by local variable $a of type int
int(5)
int(9)
int(5)
E: Cannot access uninitialized local variable $a by reference
E: Cannot access uninitialized local variable $a by reference
E: Cannot access uninitialized local variable $a by reference
E: Cannot access uninitialized local variable $a by reference
E: Cannot access uninitialized local variable $a by reference
E: Cannot access uninitialized local variable $a by reference
int(9000)
float(1500)
string(3) "zzz"
TE: Cannot increment a reference held by local variable $m of type int past its maximal value
int(9223372036854775807)
TE: Cannot decrement a reference held by local variable $k of type int past its minimal value
int(-9223372036854775808)
OK
