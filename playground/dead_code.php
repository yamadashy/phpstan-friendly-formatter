<?php

declare(strict_types=1);

/**
 * Dead code and unreachable code errors for PHPStan testing
 */

// 1. Unreachable code after return
function unreachableAfterReturn(): string
{
    return 'hello';
    echo 'This is unreachable'; // Error: unreachable
}

// 2. Unreachable code after throw
function unreachableAfterThrow(): void
{
    throw new RuntimeException('Error');
    echo 'Never executed'; // Error: unreachable
}

// 3. Always true/false condition
function alwaysTrueCondition(): void
{
    $value = 'string';
    if (is_string($value)) { // Error: always true
        echo 'Always executes';
    }
}

// 4. Impossible type check
function impossibleCheck(int $value): void
{
    if (is_string($value)) { // Error: impossible, $value is int
        echo 'Never happens';
    }
}

// 5. Unused private method
class ClassWithDeadCode
{
    public function publicMethod(): void
    {
        echo 'public';
    }

    private function unusedPrivateMethod(): void // Error: unused
    {
        echo 'never called';
    }
}
