--TEST--
Scalar methods (Tier 2): $this->prop typed as non-nullable string is a string receiver
--FILE--
<?php
// A declared, non-nullable `string` typed property of the current class bypasses
// __get and is guaranteed to be a string, so `$this->prop->method()` desugars to
// Str::method($this->prop, ...). Reading the property is a normal property fetch.
class C {
    public string $name = "  Hello  ";
    private string $greeting = "hi there";

    public function trimmed(): string {
        return $this->name->trim();
    }

    // The result of a Str method is itself a string receiver, so chaining works
    // off a typed-property receiver too.
    public function shout(): string {
        return $this->name->trim()->upper();
    }

    // Private typed string property is resolved from the current class just the same.
    public function words(): int {
        return $this->greeting->upper()->length();
    }
}

$c = new C();
var_dump($c->trimmed());
var_dump($c->shout());
var_dump($c->words());

// Works when the property is read inside a non-method... no: only $this in a method
// resolves. Re-read the same property through another method to confirm stability.
var_dump($c->trimmed());
?>
--EXPECT--
string(5) "Hello"
string(5) "HELLO"
int(8)
string(5) "Hello"
