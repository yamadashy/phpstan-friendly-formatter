<?php declare(strict_types=1);

namespace Tests\ErrorFormat;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yamadashy\PhpStanFriendlyFormatter\ErrorFormat\MessageHighlighter;

/**
 * @internal
 */
#[CoversClass(MessageHighlighter::class)]
final class MessageHighlighterTest extends TestCase
{
    #[DataProvider('provideHighlightCases')]
    public function testHighlight(string $message, string $expectedOutput): void
    {
        $messageHighlighter = new MessageHighlighter();

        self::assertSame($expectedOutput, $messageHighlighter->highlight($message));
    }

    /**
     * @return \Generator<string, string[], void, void>
     */
    public static function provideHighlightCases(): iterable
    {
        yield 'expects, given' => [
            'Parameter #1 $id of method Foo::bar() expects int, string given.',
            'Parameter #1 $id of method Foo::bar() expects <fg=green;options=bold>int</>, <fg=red;options=bold>string</> given.',
        ];

        yield 'expects, given without trailing period' => [
            'Parameter #1 $id of method Foo::bar() expects int, string given',
            'Parameter #1 $id of method Foo::bar() expects <fg=green;options=bold>int</>, <fg=red;options=bold>string</> given',
        ];

        yield 'should return, but returns' => [
            'Method Foo::bar() should return int but returns string.',
            'Method Foo::bar() should return <fg=green;options=bold>int</> but returns <fg=red;options=bold>string</>.',
        ];

        yield 'does not accept' => [
            'Property Foo::$bar (int) does not accept string.',
            'Property Foo::$bar (<fg=green;options=bold>int</>) does not accept <fg=red;options=bold>string</>.',
        ];

        yield 'nested generics with commas on both sides' => [
            'Parameter #1 $items of function process expects array<int, string>, array<string, int> given.',
            'Parameter #1 $items of function process expects <fg=green;options=bold>array\<int, string\></>, <fg=red;options=bold>array\<string, int\></> given.',
        ];

        yield 'array shapes' => [
            'Parameter #1 $user of function save expects array{id: int, name: string}, array{id: string, name: int} given.',
            'Parameter #1 $user of function save expects <fg=green;options=bold>array{id: int, name: string}</>, <fg=red;options=bold>array{id: string, name: int}</> given.',
        ];

        yield 'callable types' => [
            'Method Foo::bar() should return callable(int, string): void but returns callable(string, int): int.',
            'Method Foo::bar() should return <fg=green;options=bold>callable(int, string): void</> but returns <fg=red;options=bold>callable(string, int): int</>.',
        ];

        yield 'does not accept with an array shape' => [
            'Property Foo::$bar (array{id: int, name: string}) does not accept array{id: string}.',
            'Property Foo::$bar (<fg=green;options=bold>array{id: int, name: string}</>) does not accept <fg=red;options=bold>array{id: string}</>.',
        ];

        yield 'generator value type' => [
            'Generator expects value type int, string given.',
            'Generator expects value type <fg=green;options=bold>int</>, <fg=red;options=bold>string</> given.',
        ];

        yield 'generator key type' => [
            'Generator expects key type array<int, string>, array<string, int> given.',
            'Generator expects key type <fg=green;options=bold>array\<int, string\></>, <fg=red;options=bold>array\<string, int\></> given.',
        ];

        yield 'does not accept default value of type' => [
            'Property Foo::$bar (int) does not accept default value of type string.',
            'Property Foo::$bar (<fg=green;options=bold>int</>) does not accept default value of type <fg=red;options=bold>string</>.',
        ];

        yield 'does not accept type' => [
            "Offset 'id' (int) does not accept type string.",
            "Offset 'id' (<fg=green;options=bold>int</>) does not accept type <fg=red;options=bold>string</>.",
        ];

        yield 'does not accept value' => [
            'Constant Foo::BAR (int) does not accept value string.',
            'Constant Foo::BAR (<fg=green;options=bold>int</>) does not accept value <fg=red;options=bold>string</>.',
        ];

        yield 'object shape property does not accept type' => [
            'Property ($id) type int does not accept type string.',
            'Property ($id) type <fg=green;options=bold>int</> does not accept type <fg=red;options=bold>string</>.',
        ];

        yield 'phpDoc type is not subtype of native type: the native type is expected' => [
            'PHPDoc tag @return with type string is not subtype of native type int.',
            'PHPDoc tag @return with type <fg=red;options=bold>string</> is not subtype of native type <fg=green;options=bold>int</>.',
        ];

        yield 'phpDoc parameter type is incompatible with native type' => [
            'PHPDoc tag @param for parameter $items with type array<string, string> is incompatible with native type array<int, string>.',
            'PHPDoc tag @param for parameter $items with type <fg=red;options=bold>array\<string, string\></> is incompatible with native type <fg=green;options=bold>array\<int, string\></>.',
        ];

        yield 'var tag type is not subtype of type' => [
            'PHPDoc tag @var with type string is not subtype of type int.',
            'PHPDoc tag @var with type <fg=red;options=bold>string</> is not subtype of type <fg=green;options=bold>int</>.',
        ];

        yield 'constant phpDoc type is incompatible with value' => [
            "PHPDoc tag @var for constant Foo::BAR with type int is incompatible with value 'ten'.",
            "PHPDoc tag @var for constant Foo::BAR with type <fg=green;options=bold>int</> is incompatible with value <fg=red;options=bold>'ten'</>.",
        ];

        yield 'child return type is not covariant: the parent type is expected' => [
            'Return type array<int, string> of method Foo::bar() is not covariant with return type array<int, int> of method Base::bar().',
            'Return type <fg=red;options=bold>array\<int, string\></> of method Foo::bar() is not covariant with return type <fg=green;options=bold>array\<int, int\></> of method Base::bar().',
        ];

        yield 'child return type should be compatible: the parent type is expected' => [
            'Return type (int) of method Foo::bar() should be compatible with return type (string) of method Base::bar()',
            'Return type (<fg=red;options=bold>int</>) of method Foo::bar() should be compatible with return type (<fg=green;options=bold>string</>) of method Base::bar()',
        ];

        yield 'child parameter type is not contravariant: the parent type is expected' => [
            'Parameter #1 $items (array<int, string>) of method Foo::bar() is not contravariant with parameter #1 $items (array<int, string|int>) of method Base::bar().',
            'Parameter #1 $items (<fg=red;options=bold>array\<int, string\></>) of method Foo::bar() is not contravariant with parameter #1 $items (<fg=green;options=bold>array\<int, string|int\></>) of method Base::bar().',
        ];

        yield 'invoked with parameters: the required count is expected' => [
            'Method Foo::bar() invoked with 3 parameters, 2 required.',
            'Method Foo::bar() invoked with <fg=red;options=bold>3</> parameters, <fg=green;options=bold>2</> required.',
        ];

        yield 'invoked with a single parameter' => [
            'Function foo() invoked with 1 parameter, 2-3 required.',
            'Function foo() invoked with <fg=red;options=bold>1</> parameter, <fg=green;options=bold>2-3</> required.',
        ];

        yield 'invoked with parameters, at least required' => [
            'Function foo() invoked with 1 parameter, at least 2 required.',
            'Function foo() invoked with <fg=red;options=bold>1</> parameter, <fg=green;options=bold>at least 2</> required.',
        ];

        yield 'no match' => [
            'Access to an undefined property Foo::$bar.',
            'Access to an undefined property Foo::$bar.',
        ];

        yield 'no match is escaped' => [
            'Generator expects value type array<int, string>.',
            'Generator expects value type array\<int, string\>.',
        ];

        yield 'ambiguous separator' => [
            'Parameter #1 $id of method Foo::bar() expects int, string, float given.',
            'Parameter #1 $id of method Foo::bar() expects int, string, float given.',
        ];

        yield 'ambiguous marker' => [
            'Method Foo::bar() should return int but returns string but returns float.',
            'Method Foo::bar() should return int but returns string but returns float.',
        ];

        yield 'missing separator' => [
            'Parameter #1 $id of method Foo::bar() expects int given.',
            'Parameter #1 $id of method Foo::bar() expects int given.',
        ];

        yield 'unbalanced brackets' => [
            'Parameter #1 $id of method Foo::bar() expects array<int, string, string given.',
            'Parameter #1 $id of method Foo::bar() expects array\<int, string, string given.',
        ];
    }
}
