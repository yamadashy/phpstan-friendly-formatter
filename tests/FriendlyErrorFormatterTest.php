<?php declare(strict_types=1);

namespace Tests;

use PHPStan\Analyser\Error;
use PHPStan\Command\AnalysisResult;
use PHPStan\File\FuzzyRelativePathHelper;
use PHPStan\File\NullRelativePathHelper;
use PHPStan\File\SimpleRelativePathHelper;
use PHPStan\ShouldNotHappenException;
use PHPStan\Testing\ErrorFormatterTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestUtils\StringUtil;
use Yamadashy\PhpStanFriendlyFormatter\FriendlyErrorFormatter;

/**
 * @internal
 */
#[CoversClass(FriendlyErrorFormatter::class)]
final class FriendlyErrorFormatterTest extends ErrorFormatterTestCase
{
    /**
     * @param list<string> $expectedOutputSubstrings
     */
    #[DataProvider('provideFormatErrorsCases')]
    public function testFormatErrors(
        int $expectedExitCode,
        int $numFileErrors,
        int $numGenericErrors,
        int $numWarnings,
        array $expectedOutputSubstrings
    ): void {
        $relativePathHelper = new FuzzyRelativePathHelper(new NullRelativePathHelper(), '', [], '/');
        $simpleRelativePathHelper = new SimpleRelativePathHelper((string) getcwd());
        $formatter = new FriendlyErrorFormatter($relativePathHelper, $simpleRelativePathHelper, 3, 3, null);
        $dummyAnalysisResult = $this->getDummyAnalysisResult($numFileErrors, $numGenericErrors, $numWarnings);

        $exitCode = $formatter->formatErrors($dummyAnalysisResult, $this->getOutput());
        $outputContent = StringUtil::escapeTextColors($this->getOutputContent());
        $outputContent = StringUtil::rtrimByLines($outputContent);

        self::assertSame($expectedExitCode, $exitCode);
        foreach ($expectedOutputSubstrings as $expectedOutputSubstring) {
            self::assertStringContainsString($expectedOutputSubstring, $outputContent);
        }
    }

    /**
     * @return \Generator<string, (int|list<string>)[], void, void>
     */
    public static function provideFormatErrorsCases(): iterable
    {
        $currentDir = __DIR__;

        // Error
        yield 'No errors' => [
            0, 0, 0, 0,
            [
                '[OK] No errors',
            ],
        ];

        yield 'One file error' => [
            1, 1, 0, 0,
            [
                "{$currentDir}/data/AnalysisTargetFoo.php",
                '
    10|      */
    11|     public function targetFoo()
    12|     {
  > 13|         return 1;
    14|     }
    15|
    16| }',
                '📊 Error Identifier Summary:',
                ' <no-identifier> (in 1 file)',
                '📊 Summary:',
                '❌ Found 1 errors',
                '🏷️  In 1 error categories',
                '📂 Across 1 file',
                'ℹ️  Note:',
                '⚠️  1 errors have no identifier. Consider upgrading to PHPStan v2, which requires identifiers.',
                '[ERROR] Found 1 error',
            ],
        ];

        yield 'Two file error' => [
            1, 2, 0, 0,
            [
                '✘ Bar',
                "{$currentDir}/data/AnalysisTargetBar.php",
                '
     6| {
     7|
     8|     /**
  >  9|      * @return string
    10|      */
    11|     public function targetBar()
    12|     {
',
                '
  ✘ Foo
    10|      */
    11|     public function targetFoo()
    12|     {
  > 13|         return 1;
    14|     }
    15|
    16| }',
                '[ERROR] Found 2 errors',
            ],
        ];

        // Warning
        yield 'One warning' => [
            1, 0, 0, 1,
            [
                '⚠ first warning',
                '[WARNING] Found 0 errors and 1 warning',
            ],
        ];

        yield 'Two warning' => [
            1, 0, 0, 2,
            [
                '⚠ first warning',
                '⚠ second warning',
                '[WARNING] Found 0 errors and 2 warnings',
            ],
        ];

        // Error and warning
        yield 'One Error and one warning' => [
            1, 1, 0, 1,
            [
                '✘ Foo',
                "{$currentDir}/data/AnalysisTargetFoo.php",
                '
    10|      */
    11|     public function targetFoo()
    12|     {
  > 13|         return 1;
    14|     }
    15|
    16| }',

                '⚠ first warning',
                '[ERROR] Found 1 error and 1 warning',
            ],
        ];

        // Generic error
        yield 'One generic error' => [
            1, 0, 1, 0,
            [
                '✘ first generic error',
                '[ERROR] Found 1 error',
            ],
        ];

        yield 'Multiple generic errors' => [
            1, 0, 2, 0,
            [
                '✘ first generic error',
                '✘ second generic error',
                '[ERROR] Found 2 errors',
            ],
        ];

        yield 'Multiple errors, warnings and generic errors' => [
            1, 2, 2, 2,
            [
                '✘ Bar',
                "{$currentDir}/data/AnalysisTargetBar.php",
                '
     6| {
     7|
     8|     /**
  >  9|      * @return string
    10|      */
    11|     public function targetBar()
    12|     {',
                '✘ Foo',
                "{$currentDir}/data/AnalysisTargetFoo.php",
                '
    10|      */
    11|     public function targetFoo()
    12|     {
  > 13|         return 1;
    14|     }
    15|
    16| }',
                '✘ first generic error',
                '✘ second generic error',
                '⚠ first warning',
                '⚠ second warning',
                '[ERROR] Found 4 errors and 2 warnings',
            ],
        ];
    }

    public function testDecoratedSummaryShowsColorsAndNotes(): void
    {
        $relativePathHelper = new FuzzyRelativePathHelper(new NullRelativePathHelper(), '', [], '/');
        $simpleRelativePathHelper = new SimpleRelativePathHelper((string) getcwd());
        $formatter = new FriendlyErrorFormatter($relativePathHelper, $simpleRelativePathHelper, 3, 3, null);

        $fileErrors = [
            new Error('Ignore unmatched', __DIR__.'/data/AnalysisTargetFoo.php', 13, true, null, null, null, null, null, 'ignore.unmatched'),
            new Error('No identifier', __DIR__.'/data/AnalysisTargetFoo.php', 15),
            new Error('Non ignorable', __DIR__.'/data/AnalysisTargetBar.php', 9, false, null, null, null, null, null, 'missingType'),
        ];

        $analysisResult = $this->createAnalysisResult($fileErrors, [], []);

        $exitCode = $formatter->formatErrors($analysisResult, $this->getOutput(true));
        $outputContent = StringUtil::rtrimByLines($this->getOutputContent(true));

        self::assertSame(1, $exitCode);
        self::assertMatchesRegularExpression($this->buildErrorSummaryPattern('green', 'ignore.unmatched'), $outputContent);
        self::assertMatchesRegularExpression($this->buildErrorSummaryPattern('yellow', '<no-identifier>'), $outputContent);
        self::assertMatchesRegularExpression($this->buildErrorSummaryPattern('red', 'missingType'), $outputContent);
        self::assertStringContainsString('ℹ️  Note:', $outputContent);
        self::assertStringContainsString('🎉', $outputContent);
        self::assertStringContainsString('⚠️', $outputContent);
        self::assertStringContainsString('🚨', $outputContent);
    }

    /**
     * @throws ShouldNotHappenException
     */
    private function getDummyAnalysisResult(int $numFileErrors, int $numGenericErrors, int $numWarnings): AnalysisResult
    {
        if ($numFileErrors > 5 || $numFileErrors < 0
            || $numGenericErrors > 2 || $numGenericErrors < 0
            || $numWarnings > 2 || $numWarnings < 0) {
            throw new ShouldNotHappenException();
        }

        $fileErrors = \array_slice([
            new Error('Foo', __DIR__.'/data/AnalysisTargetFoo.php', 13),
            new Error('Bar', __DIR__.'/data/AnalysisTargetBar.php', 9),
        ], 0, $numFileErrors);
        $genericErrors = \array_slice([
            'first generic error', 'second generic error',
        ], 0, $numGenericErrors);
        $warnings = \array_slice([
            'first warning', 'second warning',
        ], 0, $numWarnings);

        return $this->createAnalysisResult($fileErrors, $genericErrors, $warnings);
    }

    /**
     * @param list<Error>  $fileErrors
     * @param list<string> $genericErrors
     * @param list<string> $warnings
     */
    private function createAnalysisResult(array $fileErrors, array $genericErrors, array $warnings): AnalysisResult
    {
        $reflectionMethod = new \ReflectionMethod(AnalysisResult::class, '__construct');
        $numOfParams = $reflectionMethod->getNumberOfParameters();

        // compatibility to less than 1.8.0
        if (7 === $numOfParams) {
            // @phpstan-ignore-next-line
            return new AnalysisResult($fileErrors, $genericErrors, [], $warnings, false, null, true);
        }

        // compatibility to less than 1.9.0
        if (8 === $numOfParams) {
            // @phpstan-ignore-next-line
            return new AnalysisResult($fileErrors, $genericErrors, [], $warnings, [], false, null, true);
        }

        // compatibility to less than 1.10.34
        if (9 === $numOfParams) {
            // @phpstan-ignore-next-line
            return new AnalysisResult($fileErrors, $genericErrors, [], $warnings, [], false, null, true, memory_get_peak_usage(true));
        }

        if (10 === $numOfParams) {
            // @phpstan-ignore-next-line
            return new AnalysisResult($fileErrors, $genericErrors, [], $warnings, [], false, null, true, memory_get_peak_usage(true), true);
        }

        if (11 === $numOfParams) {
            // @phpstan-ignore-next-line
            return new AnalysisResult($fileErrors, $genericErrors, [], $warnings, [], false, null, true, memory_get_peak_usage(true), true, []);
        }

        // @phpstan-ignore-next-line
        return new AnalysisResult($fileErrors, $genericErrors, [], $warnings, [], false, null, true, memory_get_peak_usage(true), false);
    }

    /**
     * Builds a regular expression pattern for matching ANSI-colored error summary lines.
     *
     * The pattern matches lines that contain a colored count of errors or warnings,
     * a colored identifier (such as "errors" or "warnings"), and a colored file count
     * in parentheses, using the same ANSI escape sequences as produced by the formatter.
     *
     * @param string $colorCode  One of the keys of the internal ANSI color map (e.g. 'green', 'yellow', 'red').
     * @param string $identifier the text identifier to match (for example "errors" or "warnings")
     * @param int    $count      the expected number of errors or warnings to appear in the summary
     * @param int    $fileCount  the expected number of files to appear in the summary
     *
     * @return string regular expression pattern for matching the formatted summary line
     */
    private function buildErrorSummaryPattern(string $colorCode, string $identifier, int $count = 1, int $fileCount = 1): string
    {
        $ansiColorCodes = [
            'green' => '\x1b\[32m',
            'yellow' => '\x1b\[33m',
            'red' => '\x1b\[31m',
        ];
        $colorPattern = $ansiColorCodes[$colorCode];
        $identifierColorPattern = '\x1b\[33m';
        $resetPattern = '\x1b\[[0-9;]*m';
        $escapedIdentifier = preg_quote($identifier, '/');

        return "/{$colorPattern}{$count}{$resetPattern}\\s+{$identifierColorPattern}{$escapedIdentifier}{$resetPattern} \\(in {$colorPattern}{$fileCount}{$resetPattern} files?\\)/";
    }
}
