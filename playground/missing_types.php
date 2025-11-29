<?php

declare(strict_types=1);

/**
 * Missing type declarations for PHPStan testing
 */

// 1. Missing return type
function noReturnType() // Error at higher levels: missing return type
{
    return 'hello';
}

// 2. Missing parameter type
function noParamType($value): string // Error at higher levels: missing param type
{
    return (string) $value;
}

// 3. Missing property type
class MissingTypes
{
    public $untyped; // Error at higher levels: missing property type

    private $alsoUntyped = 'default'; // Error at higher levels

    public function process($input) // Error: missing types
    {
        $this->untyped = $input;
        return $input;
    }
}

// 4. Mixed type usage
function usesMixed(mixed $value): mixed
{
    return $value->someMethod(); // Error: calling method on mixed
}
