<?php

declare(strict_types=1);

/**
 * Undefined variable/method/class errors for PHPStan testing
 */

// 1. Undefined variable
function useUndefinedVar(): void
{
    echo $undefinedVariable; // Error: undefined variable
}

// 2. Undefined method
class SomeClass
{
    public function existingMethod(): void
    {
    }
}

$obj = new SomeClass();
$obj->nonExistentMethod(); // Error: undefined method

// 3. Undefined class
$instance = new NonExistentClass(); // Error: undefined class

// 4. Undefined constant
echo UNDEFINED_CONSTANT; // Error: undefined constant

// 5. Undefined property
class AnotherClass
{
    public string $definedProperty = 'hello';
}

$another = new AnotherClass();
echo $another->undefinedProperty; // Error: undefined property

// 6. Undefined function
undefinedFunction(); // Error: undefined function
