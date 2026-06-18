--TEST--
JIT: typed-local (*_TYPED) opcodes are modeled in SSA/type-inference (no miscompile)
--EXTENSIONS--
opcache
--INI--
opcache.enable=1
opcache.enable_cli=1
opcache.jit_buffer_size=64M
opcache.jit=tracing
--FILE--
<?php
// Regression guard for the optimizer/JIT modeling of the dedicated typed-local
// write opcodes: ZEND_ASSIGN_TYPED, ZEND_ASSIGN_OP_TYPED and the four
// PRE/POST INC/DEC _TYPED. Before they were modeled as defining their op1 CV
// with the declared scalar type, the SSA carried a stale/garbage type for the
// typed local and the JIT miscompiled: VERIFY_RETURN_TYPE threw a spurious
// "Return value must be of type int, int returned" under function JIT and the
// tracing JIT tripped a type-info assertion. The function bodies below are run
// in a hot loop so they get JIT-compiled.

function ret_int(int $n): int {
    int $x = $n;     // ASSIGN_TYPED
    $x += 1;         // ASSIGN_OP_TYPED
    $x++;            // POST_INC_TYPED
    ++$x;            // PRE_INC_TYPED
    return $x;       // must be a clean int -> no spurious TypeError
}

function ret_string(string $s): string {
    string $t = $s;  // ASSIGN_TYPED
    $t .= "!";       // ASSIGN_OP_TYPED (concat)
    return $t;
}

function coerce_sum(): int {
    int $acc = 0;
    for ($i = 0; $i < 100000; $i++) {
        $acc = "5";          // weak coercion "5" -> int(5) every iter (ASSIGN_TYPED)
        $acc += $i & 1;      // ASSIGN_OP_TYPED
        $acc--;              // POST_DEC_TYPED
    }
    return $acc;
}

// overflow on a non-nullable int local must throw (declared type forbids float)
function overflow_throws(): string {
    int $x = PHP_INT_MAX;
    try {
        $x++;                // POST_INC_TYPED: overflow to float rejected
        return "no-throw";
    } catch (\TypeError $e) {
        return $e->getMessage();
    }
}

$sum = 0;
$str = "";
for ($i = 0; $i < 100000; $i++) {
    $sum += ret_int($i);
    $str = ret_string("ab");
}

var_dump($sum);
var_dump($str);
var_dump(coerce_sum());
var_dump(overflow_throws());
?>
--EXPECT--
int(5000250000)
string(3) "ab!"
int(5)
string(69) "Cannot increment local variable $x of type int past its maximal value"
--CLEAN--
<?php
?>
