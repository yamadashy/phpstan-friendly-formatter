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
