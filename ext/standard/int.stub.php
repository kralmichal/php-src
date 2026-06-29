<?php

/** @generate-class-entries */

/** @strict-properties */
final class Int
{
    /* Both methods return int|float and are therefore terminal: the result is not a
     * guaranteed-int receiver and cannot be chained as one.
     *   - pow(2, -1) === 0.5, and large exponents overflow to float.
     *   - abs(PHP_INT_MIN) === -(float)PHP_INT_MIN: the magnitude exceeds PHP_INT_MAX,
     *     so it overflows to float, exactly as the global abs(int): int|float does.
     * Declaring abs() as : int would be a lie the engine enforces — abs(PHP_INT_MIN)
     * would raise "Return value must be of type int, float returned". */
    public static function pow(int $num, int $exponent): int|float {}

    public static function abs(int $num): int|float {}

    /* clamp() always returns an int (one of $num/$min/$max), so unlike abs()/pow() it is a
     * chainable guaranteed-int method. */
    public static function clamp(int $num, int $min, int $max): int {}
}
