--TEST--
Typed local variables: cv_types and Tier-3 bridge are preserved through opcache on-disk file_cache
--EXTENSIONS--
opcache
--SKIPIF--
<?php
if (substr(PHP_OS, 0, 3) === 'WIN') die('skip not for Windows');
?>
--INI--
opcache.enable=1
opcache.enable_cli=1
opcache.file_cache_only=1
opcache.file_cache={TMP}
opcache.file_update_protection=0
--FILE--
<?php
// (1) Type enforcement: assigning a non-coercible value to a typed local throws TypeError.
function enforce_type() {
    try {
        int $x = "not-an-int";
        echo "FAIL: no TypeError\n";
    } catch (TypeError $e) {
        echo "OK: TypeError\n";
    }
}

// (2) Tier-3 bridge: string-typed local desugars ->method() to Str::method().
function tier3_bridge(string $input) {
    string $s = $input;
    echo $s->trim(), "\n";
}

// (3) Taking a reference to an uninitialised typed local is forbidden.
function uninit_ref() {
    try {
        int $x;
        $r = &$x;
        echo "FAIL: no error\n";
    } catch (Error $e) {
        echo "OK: " . $e->getMessage() . "\n";
    }
}

enforce_type();
tier3_bridge("  world  ");
uninit_ref();
?>
--EXPECT--
OK: TypeError
world
OK: Cannot access uninitialized local variable $x by reference
--CLEAN--
<?php
// Remove .bin cache files written by this test under sys_get_temp_dir().
if (substr(PHP_OS, 0, 3) !== 'WIN') {
    $tmp = sys_get_temp_dir();
    $base = __FILE__;
    // opcache stores files as <cache>/<hash>/<realpath>.bin
    $pattern = $tmp . '/*/' . $tmp . '/*' . basename($base, '.php') . '*.bin';
    foreach (glob($pattern) as $p) {
        @unlink($p);
        $d = dirname($p);
        while (strlen($d) > strlen($tmp)) {
            @rmdir($d);
            $d = dirname($d);
        }
    }
}
?>
