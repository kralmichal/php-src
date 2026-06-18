--TEST--
Typed local variables: a CV that is both reference-aliased (&$s) and dynamically written stays enforced
--FILE--
<?php
function f() {
    string $s = 'a';
    $r = &$s;                 // typed CV becomes a typed reference
    extract(['s' => 'c']);    // by-name write through the same slot
    var_dump($s, $r);         // both observe "c"

    $n = 's';
    $$n = 'd';                // another by-name write
    var_dump($s, $r);

    // wrong-type write through the alias is still rejected
    try {
        $r = [];
    } catch (\TypeError $e) {
        echo $e->getMessage(), "\n";
    }
    var_dump($s, $r);

    unset($r);
    // after dropping the alias the by-name path still enforces
    $$n = 5;                  // coerced to string("5")
    var_dump($s);
}
f();
?>
--EXPECT--
string(1) "c"
string(1) "c"
string(1) "d"
string(1) "d"
Cannot assign array to reference held by local variable $s of type string
string(1) "d"
string(1) "d"
string(1) "5"
