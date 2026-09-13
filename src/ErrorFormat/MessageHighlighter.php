<?php declare(strict_types=1);

namespace Yamadashy\PhpStanFriendlyFormatter\ErrorFormat;

use Symfony\Component\Console\Formatter\OutputFormatter;

/**
 * Best-effort highlighting of the expected and the actual type inside PHPStan error messages.
 *
 * Types may contain commas and brackets (`array<int, string>`, `array{id: int}`, `callable(int): void`),
 * so the separator between the two types is located with a bracket-depth aware scanner.
 * When the message does not match a known shape, or the split would be ambiguous,
 * the message is returned escaped but otherwise unchanged.
 */
class MessageHighlighter
{
    private const EXPECTED_STYLE = 'fg=green;options=bold';

    private const ACTUAL_STYLE = 'fg=yellow;options=bold';

    /** @var array<string, string> */
    private const BRACKET_PAIRS = [
        '<' => '>',
        '(' => ')',
        '[' => ']',
        '{' => '}',
    ];

    public function highlight(string $message): string
    {
        $parts = $this->splitMessage($message);

        if (null === $parts) {
            return OutputFormatter::escape($message);
        }

        [$prefix, $expected, $separator, $actual, $suffix] = $parts;

        return OutputFormatter::escape($prefix)
            .'<'.self::EXPECTED_STYLE.'>'.OutputFormatter::escape($expected).'</>'
            .OutputFormatter::escape($separator)
            .'<'.self::ACTUAL_STYLE.'>'.OutputFormatter::escape($actual).'</>'
            .OutputFormatter::escape($suffix);
    }

    /**
     * @return null|array{string, string, string, string, string} [prefix, expected, separator, actual, suffix]
     */
    private function splitMessage(string $message): ?array
    {
        $period = str_ends_with($message, '.') ? '.' : '';
        $body = '' === $period ? $message : substr($message, 0, -1);

        return $this->splitExpectsGiven($body, $period)
            ?? $this->splitShouldReturnButReturns($body, $period)
            ?? $this->splitDoesNotAccept($body, $period);
    }

    /**
     * `... expects <EXPECTED>, <ACTUAL> given`.
     *
     * @return null|array{string, string, string, string, string}
     */
    private function splitExpectsGiven(string $body, string $period): ?array
    {
        $marker = ' expects ';
        $trailer = ' given';

        $markerPosition = $this->singleTopLevelPosition($body, $marker);

        if (null === $markerPosition || !str_ends_with($body, $trailer)) {
            return null;
        }

        $typesStart = $markerPosition + \strlen($marker);
        $typesLength = \strlen($body) - \strlen($trailer) - $typesStart;

        if ($typesLength <= 0) {
            return null;
        }

        $types = substr($body, $typesStart, $typesLength);
        $separator = ', ';
        $separatorPosition = $this->singleTopLevelPosition($types, $separator);

        if (null === $separatorPosition) {
            return null;
        }

        $expected = substr($types, 0, $separatorPosition);
        $actual = substr($types, $separatorPosition + \strlen($separator));

        if ('' === $expected || '' === $actual) {
            return null;
        }

        return [
            substr($body, 0, $typesStart),
            $expected,
            $separator,
            $actual,
            $trailer.$period,
        ];
    }

    /**
     * `... should return <EXPECTED> but returns <ACTUAL>`.
     *
     * @return null|array{string, string, string, string, string}
     */
    private function splitShouldReturnButReturns(string $body, string $period): ?array
    {
        $marker = ' should return ';
        $separator = ' but returns ';

        $markerPosition = $this->singleTopLevelPosition($body, $marker);

        if (null === $markerPosition) {
            return null;
        }

        $typesStart = $markerPosition + \strlen($marker);
        $types = substr($body, $typesStart);
        $separatorPosition = $this->singleTopLevelPosition($types, $separator);

        if (null === $separatorPosition) {
            return null;
        }

        $expected = substr($types, 0, $separatorPosition);
        $actual = substr($types, $separatorPosition + \strlen($separator));

        if ('' === $expected || '' === $actual) {
            return null;
        }

        return [
            substr($body, 0, $typesStart),
            $expected,
            $separator,
            $actual,
            $period,
        ];
    }

    /**
     * `... (<EXPECTED>) does not accept <ACTUAL>`.
     *
     * @return null|array{string, string, string, string, string}
     */
    private function splitDoesNotAccept(string $body, string $period): ?array
    {
        $marker = ') does not accept ';

        $markerPosition = $this->singleTopLevelPosition($body, ' does not accept ');

        if (null === $markerPosition || ')' !== substr($body, $markerPosition - 1, 1)) {
            return null;
        }

        $openPosition = $this->findOpeningBracketPosition($body, $markerPosition);

        if (null === $openPosition) {
            return null;
        }

        $expected = substr($body, $openPosition + 1, $markerPosition - 1 - ($openPosition + 1));
        $actual = substr($body, $markerPosition - 1 + \strlen($marker));

        if ('' === $expected || '' === $actual) {
            return null;
        }

        return [
            substr($body, 0, $openPosition + 1),
            $expected,
            $marker,
            $actual,
            $period,
        ];
    }

    /**
     * Position of the only occurrence of the needle outside of any bracket, or null when it does not
     * occur exactly once.
     */
    private function singleTopLevelPosition(string $haystack, string $needle): ?int
    {
        $positions = [];
        $stack = [];
        $closingBrackets = array_flip(self::BRACKET_PAIRS);
        $length = \strlen($haystack);
        $needleLength = \strlen($needle);

        for ($i = 0; $i < $length; ++$i) {
            $character = $haystack[$i];

            if ([] === $stack && substr($haystack, $i, $needleLength) === $needle) {
                $positions[] = $i;
            }

            if (isset(self::BRACKET_PAIRS[$character])) {
                $stack[] = $character;

                continue;
            }

            if (isset($closingBrackets[$character]) && [] !== $stack && end($stack) === $closingBrackets[$character]) {
                array_pop($stack);
            }
        }

        return 1 === \count($positions) ? $positions[0] : null;
    }

    /**
     * Position of the bracket opening the group that ends right before the given position.
     */
    private function findOpeningBracketPosition(string $haystack, int $closePosition): ?int
    {
        $depth = 0;
        $closingBrackets = array_flip(self::BRACKET_PAIRS);

        for ($i = $closePosition - 1; $i >= 0; --$i) {
            $character = $haystack[$i];

            if (isset($closingBrackets[$character])) {
                ++$depth;

                continue;
            }

            if (isset(self::BRACKET_PAIRS[$character])) {
                --$depth;

                if (0 === $depth) {
                    return '(' === $character ? $i : null;
                }
            }
        }

        return null;
    }
}
