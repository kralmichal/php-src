--TEST--
Typed local variables: extract() onto a typed local is type-checked (coerce + reject + valid)
--FILE--
<?php
function valid() {
    string $s = 'a';
    extract(['s' => 'b']);
    var_dump($s);
}
valid();

function coerce() {
    int $i = 1;
    extract(['i' => '7']);
    var_dump($i);
}
coerce();

function reject() {
    int $i = 1;
    try {
        extract(['i' => 'not an int']);
    } catch (\TypeError $e) {
        echo $e->getMessage(), "\n";
    }
    var_dump($i);
}
reject();
?>
--EXPECT--
string(1) "b"
int(7)
Cannot assign string to reference held by local variable $i of type int
int(1)
