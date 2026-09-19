<?php

declare(strict_types=1);

/**
 * Errors that pair an expected (declared) type with the actual one.
 */

// 1. Generator value type
/**
 * @return Generator<int, string>
 */
function generateStrings(): Generator
{
    yield 1; // Error: Generator expects value type string, int given
}

// 2. Property default value
class DefaultValueHolder
{
    /** @var int */
    public $count = 'zero'; // Error: does not accept default value of type string
}

// 3. Class constant value
class ConstantHolder
{
    /** @var int */
    public const LIMIT = 'ten'; // Error: @var int is incompatible with value 'ten'
}

// 4. Overriding method return type
interface ValueProvider
{
    public function provide(): string;
}

class IntValueProvider implements ValueProvider
{
    public function provide(): int // Error: not compatible with the interface return type
    {
        return 1;
    }
}

// 5. PHPDoc type incompatible with the native type
class PhpDocHolder
{
    /**
     * @return string
     */
    public function count(): int // Error: @return is incompatible with native type int
    {
        return 1;
    }
}

// 6. Overriding method parameter type
class ParentClass
{
    public function accept(int|string $value): void
    {
        echo $value;
    }
}

class ChildClass extends ParentClass
{
    public function accept(int $value): void // Error: not contravariant with the parent parameter
    {
        echo $value;
    }
}

// 7. Argument count
function needsTwoArguments(int $first, int $second): void
{
    echo $first + $second;
}

needsTwoArguments(1); // Error: invoked with 1 parameter, 2 required
