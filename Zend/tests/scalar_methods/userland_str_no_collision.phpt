--TEST--
Scalar methods: a userland `class Str {}` does not collide with the internal backing class
--FILE--
<?php
// Because the backing class is registered under an internal-only name (not "Str"),
// userland is free to declare its own `Str`. This must NOT fatal with
// "Cannot redeclare class Str", and the userland symbol must be the one userland sees.
class Str {
    public int $x = 1;
    public static function trim(string $s): string { return "USERLAND:$s"; }
}

// The userland class declares and instantiates normally.
$o = new Str();
var_dump($o->x);

// `Str` now resolves to the *userland* class for every userland-visible operation.
var_dump(class_exists('Str'));
var_dump(Str::trim("z"));
var_dump((new ReflectionClass('Str'))->getName());

// The scalar-method desugar is unaffected by the presence of a userland `Str`: it still
// dispatches to the internal backing class, not to userland Str::trim().
var_dump("  hi  "->trim());
var_dump("  hi  "->trim()->upper());
string $s = '  spaced  ';
var_dump($s->trim());
?>
--EXPECT--
int(1)
bool(true)
string(10) "USERLAND:z"
string(3) "Str"
string(2) "hi"
string(2) "HI"
string(6) "spaced"
