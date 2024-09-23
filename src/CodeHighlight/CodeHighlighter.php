<?php declare(strict_types=1);

namespace Yamadashy\PhpStanFriendlyFormatter\CodeHighlight;

use JakubOnderka\PhpConsoleColor\ConsoleColor as LegacyConsoleColor;
use JakubOnderka\PhpConsoleHighlighter\Highlighter as LegacyHighlighter;
use PHP_Parallel_Lint\PhpConsoleColor\ConsoleColor;
use PHP_Parallel_Lint\PhpConsoleHighlighter\Highlighter;

class CodeHighlighter
{
    /**
     * @var FallbackHighlighter|Highlighter|LegacyHighlighter
     *
     * @phpstan-ignore class.notFound
     */
    private $highlighter;

    public function __construct()
    {
        if (
            class_exists('\PHP_Parallel_Lint\PhpConsoleHighlighter\Highlighter')
            && class_exists('\PHP_Parallel_Lint\PhpConsoleColor\ConsoleColor')
        ) {
            // Support Highlighter and ConsoleColor 1.0+.
            $colors = new ConsoleColor();
            $this->highlighter = new Highlighter($colors);
        } elseif (
            class_exists('\JakubOnderka\PhpConsoleHighlighter\Highlighter')
            && class_exists('\JakubOnderka\PhpConsoleColor\ConsoleColor')
        ) {
            // Support Highlighter and ConsoleColor < 1.0.
            $colors = new LegacyConsoleColor();
            $this->highlighter = new LegacyHighlighter($colors);
        } else {
            // Fallback to non-highlighted output
            $this->highlighter = new FallbackHighlighter();
        }
    }

    public function highlight(string $fileContent, int $lineNumber, int $lineBefore, int $lineAfter): string
    {
        /** @phpstan-ignore class.notFound */
        $content = $this->highlighter->getCodeSnippet(
            $fileContent,
            $lineNumber,
            $lineBefore,
            $lineAfter
        );

        return rtrim($content, "\n");
    }
}
