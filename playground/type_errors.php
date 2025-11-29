<?php

declare(strict_types=1);

/**
 * Type-related errors for PHPStan testing
 */

// 1. Wrong return type
function getString(): string
{
    return 123; // Error: should return string
}

// 2. Wrong argument type
function expectsInt(int $value): void
{
    echo $value;
}

expectsInt('not an int'); // Error: wrong argument type

// 3. Nullable type not handled
function processString(string $value): int
{
    return strlen($value);
}

function mayReturnNull(): ?string
{
    return rand(0, 1) ? 'hello' : null;
}

processString(mayReturnNull()); // Error: might be null

// 4. Array type mismatch
/** @param array<int, string> $items */
function processStringArray(array $items): void
{
    foreach ($items as $item) {
        echo $item;
    }
}

processStringArray([1, 2, 3]); // Error: array of int, not string

// 5. Property type mismatch
class TypedClass
{
    public string $name;

    public function __construct()
    {
        $this->name = 42; // Error: wrong type
    }
}
