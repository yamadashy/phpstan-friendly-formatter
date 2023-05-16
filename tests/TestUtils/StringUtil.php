<?php declare(strict_types=1);

namespace Tests\TestUtils;

class StringUtil
{
    /**
     * Remove color representation of text.
     */
    public static function escapeTextColors(string $text): string
    {
        return (string) preg_replace('/\e\[\d+m/', '', $text);
    }

    /**
     * Trim line endings line by line.
     */
    public static function rtrimByLines(string $text): string
    {
        $lines = explode(PHP_EOL, $text);
        $lines = array_map(static function ($line) {
            return rtrim($line);
        }, $lines);

        return implode(PHP_EOL, $lines);
    }
}
