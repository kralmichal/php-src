<?php

/** @generate-class-entries */

/** @strict-properties */
final class Str
{
    public static function trim(string $string, string $characters = " \n\r\t\v\0"): string {}

    public static function upper(string $string): string {}

    public static function lower(string $string): string {}

    public static function length(string $string): int {}

    public static function contains(string $string, string $needle): bool {}

    public static function startsWith(string $string, string $prefix): bool {}

    public static function endsWith(string $string, string $suffix): bool {}
}
