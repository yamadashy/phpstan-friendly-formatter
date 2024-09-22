<?php declare(strict_types=1);

namespace Yamadashy\PhpStanFriendlyFormatter;

use JakubOnderka\PhpConsoleColor\ConsoleColor as OldConsoleColor;
use JakubOnderka\PhpConsoleHighlighter\Highlighter as OldHighlighter;
use PHP_Parallel_Lint\PhpConsoleColor\ConsoleColor;
use PHP_Parallel_Lint\PhpConsoleHighlighter\Highlighter;

class CodeHighlighter
{
    /** @var FallbackHighlighter|Highlighter|OldHighlighter */
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
            $colors = new OldConsoleColor();
            $this->highlighter = new OldHighlighter($colors);
        } else {
            // Fallback to non-highlighted output
            $this->highlighter = new FallbackHighlighter();
        }
    }

    public function highlight(string $fileContent, int $lineNumber, int $lineBefore, int $lineAfter): string
    {
        $content = $this->highlighter->getCodeSnippet(
            $fileContent,
            $lineNumber,
            $lineBefore,
            $lineAfter
        );

        return rtrim($content, "\n");
    }
}
