<?php

declare(strict_types=1);

/**
 * Logic and comparison errors for PHPStan testing
 */

// 1. Strict comparison with different types
function strictComparison(): bool
{
    return 'string' === 123; // Error: comparing different types
}

// 2. Division by zero
function divisionByZero(): float
{
    $divisor = 0;
    return 100 / $divisor; // Error: division by zero
}

// 3. Array access on non-array
function arrayAccessOnString(): string
{
    $value = 'hello';
    return $value['key']; // Error: invalid array access
}

// 4. Instanceof with final class that doesn't match
final class FinalClass
{
}

class OtherClass
{
}

function impossibleInstanceof(OtherClass $obj): bool
{
    return $obj instanceof FinalClass; // Error: impossible
}

// 5. Wrong number of arguments
function expectsThreeArgs(int $a, int $b, int $c): int
{
    return $a + $b + $c;
}

$result = expectsThreeArgs(1, 2); // Error: missing argument

// 6. Duplicate array key
function duplicateKeys(): array
{
    return [
        'key' => 'first',
        'key' => 'second', // Error: duplicate key
    ];
}
