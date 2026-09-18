<?php

declare(strict_types=1);

namespace Einvoicing\Support;

/**
 * Reading decoded JSON without trusting it.
 *
 * Everything that arrives over the wire is `mixed`, and a response object that
 * casts blindly is one field rename away from a TypeError in someone else's
 * production. These readers take what is there when it is the right type and
 * fall back when it is not, so a surprising payload degrades instead of
 * exploding.
 *
 * @internal
 */
final class Data
{
    /** @param array<array-key, mixed> $data */
    public static function string(array $data, string $key, string $default = ''): string
    {
        $value = $data[$key] ?? null;

        if (is_string($value)) {
            return $value;
        }

        return is_int($value) || is_float($value) ? (string) $value : $default;
    }

    /** @param array<array-key, mixed> $data */
    public static function nullableString(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        return is_string($value) ? $value : null;
    }

    /** @param array<array-key, mixed> $data */
    public static function bool(array $data, string $key, bool $default = false): bool
    {
        $value = $data[$key] ?? null;

        return is_bool($value) ? $value : $default;
    }

    /** @param array<array-key, mixed> $data */
    public static function int(array $data, string $key, int $default = 0): int
    {
        $value = $data[$key] ?? null;

        return is_int($value) ? $value : (is_numeric($value) ? (int) $value : $default);
    }

    /**
     * A nested object.
     *
     * @param  array<array-key, mixed>  $data
     * @return array<string, mixed>
     */
    public static function map(array $data, string $key): array
    {
        $value = $data[$key] ?? null;

        if (! is_array($value)) {
            return [];
        }

        /** @var array<string, mixed> $value */
        return $value;
    }

    /**
     * A nested object that may legitimately be absent.
     *
     * @param  array<array-key, mixed>  $data
     * @return array<string, mixed>|null
     */
    public static function nullableMap(array $data, string $key): ?array
    {
        $value = $data[$key] ?? null;

        if (! is_array($value)) {
            return null;
        }

        /** @var array<string, mixed> $value */
        return $value;
    }

    /**
     * An array of objects, with anything that is not one dropped.
     *
     * @param  array<array-key, mixed>  $data
     * @return list<array<string, mixed>>
     */
    public static function maps(array $data, string $key): array
    {
        $value = $data[$key] ?? null;

        if (! is_array($value)) {
            return [];
        }

        /** @var list<array<string, mixed>> $maps */
        $maps = array_values(array_filter($value, 'is_array'));

        return $maps;
    }

    /**
     * An array of strings, with anything else dropped.
     *
     * @param  array<array-key, mixed>  $data
     * @return list<string>
     */
    public static function strings(array $data, string $key): array
    {
        $value = $data[$key] ?? null;

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, 'is_string'));
    }
}
