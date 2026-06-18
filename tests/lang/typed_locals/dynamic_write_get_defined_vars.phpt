--TEST--
Typed local variables: promotion to a reference is transparent to get_defined_vars()/compact()
--FILE--
<?php
function f() {
    string $s = 'hello';
    int $i = 42;
    $plain = 'p';

    // Materialize the symbol table (promotes typed locals to references).
    extract(['s' => 'world']);

    // Reads must dereference: values, not references.
    var_dump(get_defined_vars());
    var_dump(compact('s', 'i', 'plain'));
}
f();
?>
--EXPECT--
array(3) {
  ["s"]=>
  string(5) "world"
  ["i"]=>
  int(42)
  ["plain"]=>
  string(1) "p"
}
array(3) {
  ["s"]=>
  string(5) "world"
  ["i"]=>
  int(42)
  ["plain"]=>
  string(1) "p"
}
