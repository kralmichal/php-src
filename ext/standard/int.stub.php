<?php

/** @generate-class-entries */

/** @strict-properties */
final class Int
{
    /* pow() returns int|float (e.g. pow(2, -1) === 0.5), so this is a terminal
     * method: its result is not a guaranteed-int receiver and cannot be chained
     * as one. */
    public static function pow(int $num, int $exponent): int|float {}

    public static function abs(int $num): int {}
}
