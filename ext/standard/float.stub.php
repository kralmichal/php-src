<?php

/** @generate-class-entries */

/** @strict-properties */
final class Float
{
    /* Every method returns exactly float (a float op cannot overflow to another type), so all
     * four are chainable guaranteed-float receivers. */
    public static function round(float $num, int $precision = 0): float {}

    public static function ceil(float $num): float {}

    public static function floor(float $num): float {}

    public static function abs(float $num): float {}
}
