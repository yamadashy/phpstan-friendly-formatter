<?php declare(strict_types=1);

namespace Yamadashy\PhpStanFriendlyFormatter\CodeHighlight;

/**
 * @see Highlighter
 */
class FallbackHighlighter
{
    public function getCodeSnippet(string $fileContent, int $lineNumber, int $lineBefore, int $lineAfter): string
    {
        $lines = explode("\n", $fileContent);
        $totalLines = \count($lines);

        $startLine = max(1, $lineNumber - $lineBefore);
        $endLine = min($totalLines, $lineNumber + $lineAfter);

        $snippet = '';
        $lineNumberWidth = \strlen((string) $endLine);

        for ($i = $startLine; $i <= $endLine; ++$i) {
            $currentLine = $lines[$i - 1] ?? '';
            $linePrefix = '<fg=gray>'.str_pad((string) $i, $lineNumberWidth, ' ', STR_PAD_LEFT).'| </>';
            $snippet .= ($lineNumber === $i ? '<fg=red>  > </>' : '    ').$linePrefix.$currentLine."\n";
        }

        return rtrim($snippet);
    }
}
