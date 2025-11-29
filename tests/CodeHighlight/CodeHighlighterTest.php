<?php declare(strict_types=1);

namespace Tests\CodeHighlight;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\TestUtils\StringUtil;
use Yamadashy\PhpStanFriendlyFormatter\CodeHighlight\CodeHighlighter;

/**
 * @internal
 */
#[CoversClass(CodeHighlighter::class)]
final class CodeHighlighterTest extends TestCase
{
    #[DataProvider('provideHighlightCases')]
    public function testHighlight(
        string $filePath,
        int $lineNumber,
        int $lineBefore,
        int $lineAfter,
        string $expectedOutput
    ): void {
        $codeHighlighter = new CodeHighlighter();

        $fileContent = (string) file_get_contents($filePath);

        $output = $codeHighlighter->highlight($fileContent, $lineNumber, $lineBefore, $lineAfter);
        $output = StringUtil::escapeTextColors($output);
        $output = StringUtil::rtrimByLines($output);

        self::assertSame($expectedOutput, $output);
    }

    /**
     * @return \Generator<string, (int|string)[], void, void>
     */
    public static function provideHighlightCases(): iterable
    {
        yield 'show 3 lines before and after' => [
            __DIR__.'/../data/AnalysisTargetFoo.php',
            11,
            3,
            3,
            '     8|     /**
     9|      * @return string
    10|      */
  > 11|     public function targetFoo()
    12|     {
    13|         return 1;
    14|     }',
        ];

        yield 'show 5 lines before' => [
            __DIR__.'/../data/AnalysisTargetFoo.php',
            11,
            5,
            3,
            '     6| {
     7|
     8|     /**
     9|      * @return string
    10|      */
  > 11|     public function targetFoo()
    12|     {
    13|         return 1;
    14|     }',
        ];

        yield 'show 6 lines after' => [
            __DIR__.'/../data/AnalysisTargetFoo.php',
            11,
            3,
            6,
            '     8|     /**
     9|      * @return string
    10|      */
  > 11|     public function targetFoo()
    12|     {
    13|         return 1;
    14|     }
    15|
    16| }
    17|',
        ];

        yield 'show 1 line only' => [
            __DIR__.'/../data/AnalysisTargetFoo.php',
            11,
            0,
            0,
            '  > 11|     public function targetFoo()',
        ];

        yield 'show 1 line of Bar' => [
            __DIR__.'/../data/AnalysisTargetBar.php',
            13,
            0,
            0,
            '  > 13|         return 2;',
        ];
    }
}
