<?php declare(strict_types=1);

namespace Yamadashy\PhpStanFriendlyFormatter\ErrorFormat;

use Symfony\Component\Console\Formatter\OutputFormatter;

/**
 * Best-effort highlighting of the expected and the actual type inside PHPStan error messages and tips.
 *
 * Colours are applied by ROLE, never by position: the EXPECTED type (the declared contract: a parameter
 * type, a declared return type, a property type, the parent method signature, the native type) is green,
 * the ACTUAL type (what was found at the error location) is red. Nothing else is coloured.
 * Some messages put the actual type first (`Return type (X) of method ... should be compatible with (Y)`),
 * so every shape in the table below declares which side is the expected one.
 *
 * Types may contain commas and brackets (`array<int, string>`, `array{id: int}`, `callable(int): void`),
 * so the separators are located with a bracket-depth aware scanner. When the message does not match a
 * known shape, or the split would be ambiguous, the message is returned escaped but otherwise unchanged.
 */
class MessageHighlighter
{
    private const EXPECTED_STYLE = 'fg=green;options=bold';

    private const ACTUAL_STYLE = 'fg=red;options=bold';

    /**
     * Shapes made of literal delimiters: `<prefix><opening><A><sideTrailer><separator><B><sideTrailer><trailer>`.
     *
     * The opening and the separator must both occur exactly once outside of any bracket, and the
     * trailer (when not empty) must end the message. When a side trailer is given, both types end at
     * their single occurrence of it. More specific shapes come first, because the first matching row wins.
     *
     * @var list<array{opening: string, separator: string, trailer: string, sideTrailer: string, expectedFirst: bool}>
     */
    private const DELIMITED_SHAPES = [
        // Generator expects value type X, Y given. (generator.valueType, generator.keyType, generator.sendType)
        ['opening' => ' expects value type ', 'separator' => ', ', 'trailer' => ' given', 'sideTrailer' => '', 'expectedFirst' => true],
        ['opening' => ' expects key type ', 'separator' => ', ', 'trailer' => ' given', 'sideTrailer' => '', 'expectedFirst' => true],
        ['opening' => ' expects delegated TSend type ', 'separator' => ', ', 'trailer' => ' given', 'sideTrailer' => '', 'expectedFirst' => true],
        // Parameter #1 $x of method C::m() expects X, Y given. (argument.type, paramOut.type)
        ['opening' => ' expects ', 'separator' => ', ', 'trailer' => ' given', 'sideTrailer' => '', 'expectedFirst' => true],
        // Method C::m() should return X but returns Y. (return.type, generator.returnType)
        ['opening' => ' should return ', 'separator' => ' but returns ', 'trailer' => '', 'sideTrailer' => '', 'expectedFirst' => true],
        // Property C::$p (X) does not accept default value of type Y. (property.defaultValue)
        ['opening' => ' (', 'separator' => ') does not accept default value of type ', 'trailer' => '', 'sideTrailer' => '', 'expectedFirst' => true],
        // Offset 'id' (X) does not accept type Y. (tip of argument.type and friends)
        ['opening' => ' (', 'separator' => ') does not accept type ', 'trailer' => '', 'sideTrailer' => '', 'expectedFirst' => true],
        // Constant C::FOO (X) does not accept value Y. (classConstant.type)
        ['opening' => ' (', 'separator' => ') does not accept value ', 'trailer' => '', 'sideTrailer' => '', 'expectedFirst' => true],
        // Property C::$p (X) does not accept Y. (assign.propertyType, offsetAssign.valueType)
        ['opening' => ' (', 'separator' => ') does not accept ', 'trailer' => '', 'sideTrailer' => '', 'expectedFirst' => true],
        // Property ($p) type X does not accept type Y. (tip on object shapes)
        ['opening' => ') type ', 'separator' => ' does not accept type ', 'trailer' => '', 'sideTrailer' => '', 'expectedFirst' => true],
        // PHPDoc tag @return with type X is not subtype of native type Y. The native type is the contract.
        // (return.phpDocType, parameter.phpDocType, property.phpDocType, varTag.nativeType, varTag.type)
        ['opening' => ' with type ', 'separator' => ' is not subtype of native type ', 'trailer' => '', 'sideTrailer' => '', 'expectedFirst' => false],
        ['opening' => ' with type ', 'separator' => ' is incompatible with native type ', 'trailer' => '', 'sideTrailer' => '', 'expectedFirst' => false],
        ['opening' => ' with type ', 'separator' => ' is not subtype of type ', 'trailer' => '', 'sideTrailer' => '', 'expectedFirst' => false],
        // PHPDoc tag @var for constant C::FOO with type X is incompatible with value Y. (classConstant.phpDocType)
        ['opening' => ' with type ', 'separator' => ' is incompatible with value ', 'trailer' => '', 'sideTrailer' => '', 'expectedFirst' => true],
        // Return type X of method C::m() is not covariant with return type Y of method P::m().
        // The parent method is the contract. (method.childReturnType)
        ['opening' => 'Return type ', 'separator' => ' is not covariant with return type ', 'trailer' => '', 'sideTrailer' => ' of method ', 'expectedFirst' => false],
        ['opening' => 'Return type ', 'separator' => ' is not compatible with return type ', 'trailer' => '', 'sideTrailer' => ' of method ', 'expectedFirst' => false],
        // Method C::m() invoked with 3 parameters, 2 required. (arguments.count)
        ['opening' => ' invoked with ', 'separator' => ' parameters, ', 'trailer' => ' required', 'sideTrailer' => '', 'expectedFirst' => false],
        ['opening' => ' invoked with ', 'separator' => ' parameter, ', 'trailer' => ' required', 'sideTrailer' => '', 'expectedFirst' => false],
    ];

    /**
     * Shapes with two parenthesised types around a literal anchor, such as
     * `Return type (X) of method C::m() should be compatible with return type (Y) of method P::m()`.
     *
     * The child (actual) signature always comes first and the parent (expected) one second.
     *
     * @var list<string>
     */
    private const BRACKETED_PAIR_ANCHORS = [
        ' should be compatible with ',
        ' should be covariant with ',
        ' should be contravariant with ',
        ' is not covariant with ',
        ' is not contravariant with ',
        ' is not compatible with ',
        ' does not match parameter ',
    ];

    /** @var array<string, string> */
    private const BRACKET_PAIRS = [
        '<' => '>',
        '(' => ')',
        '[' => ']',
        '{' => '}',
    ];

    public function highlight(string $message): string
    {
        return $this->tryHighlight($message) ?? OutputFormatter::escape($message);
    }

    /**
     * Same as highlight(), but returns null when the message does not match any known shape,
     * so that the caller can keep the original string (PHPStan tips may contain their own markup).
     */
    public function tryHighlight(string $message): ?string
    {
        $parts = $this->splitMessage($message);

        if (null === $parts) {
            return null;
        }

        [$prefix, $first, $separator, $second, $suffix, $expectedFirst] = $parts;

        $firstStyle = $expectedFirst ? self::EXPECTED_STYLE : self::ACTUAL_STYLE;
        $secondStyle = $expectedFirst ? self::ACTUAL_STYLE : self::EXPECTED_STYLE;

        return OutputFormatter::escape($prefix)
            .'<'.$firstStyle.'>'.OutputFormatter::escape($first).'</>'
            .OutputFormatter::escape($separator)
            .'<'.$secondStyle.'>'.OutputFormatter::escape($second).'</>'
            .OutputFormatter::escape($suffix);
    }

    /**
     * @return null|array{string, string, string, string, string, bool} [prefix, first, separator, second, suffix, expectedFirst]
     */
    private function splitMessage(string $message): ?array
    {
        $period = str_ends_with($message, '.') ? '.' : '';
        $body = '' === $period ? $message : substr($message, 0, -1);

        foreach (self::BRACKETED_PAIR_ANCHORS as $anchor) {
            $parts = $this->splitBracketedPair($body, $period, $anchor);

            if (null !== $parts) {
                return $parts;
            }
        }

        foreach (self::DELIMITED_SHAPES as $shape) {
            $parts = $this->splitDelimited($body, $period, $shape);

            if (null !== $parts) {
                return $parts;
            }
        }

        return null;
    }

    /**
     * @param array{opening: string, separator: string, trailer: string, sideTrailer: string, expectedFirst: bool} $shape
     *
     * @return null|array{string, string, string, string, string, bool}
     */
    private function splitDelimited(string $body, string $period, array $shape): ?array
    {
        $opening = $shape['opening'];
        $separator = $shape['separator'];
        $trailer = $shape['trailer'];
        $sideTrailer = $shape['sideTrailer'];

        $openingPosition = $this->singleTopLevelPosition($body, $opening);

        if (null === $openingPosition) {
            return null;
        }

        $regionStart = $openingPosition + \strlen($opening);
        $regionEnd = \strlen($body);

        if ('' !== $trailer) {
            if (!str_ends_with($body, $trailer)) {
                return null;
            }

            $regionEnd -= \strlen($trailer);
        }

        if ($regionEnd <= $regionStart) {
            return null;
        }

        $region = substr($body, $regionStart, $regionEnd - $regionStart);
        $separatorPosition = $this->singleTopLevelPosition($region, $separator);

        if (null === $separatorPosition) {
            return null;
        }

        $first = substr($region, 0, $separatorPosition);
        $second = substr($region, $separatorPosition + \strlen($separator));
        $middle = $separator;
        $suffix = substr($body, $regionEnd).$period;

        if ('' !== $sideTrailer) {
            $firstCut = $this->singleTopLevelPosition($first, $sideTrailer);
            $secondCut = $this->singleTopLevelPosition($second, $sideTrailer);

            if (null === $firstCut || null === $secondCut) {
                return null;
            }

            $middle = substr($first, $firstCut).$middle;
            $suffix = substr($second, $secondCut).$suffix;
            $first = substr($first, 0, $firstCut);
            $second = substr($second, 0, $secondCut);
        }

        if ('' === $first || '' === $second) {
            return null;
        }

        return [
            substr($body, 0, $regionStart),
            $first,
            $middle,
            $second,
            $suffix,
            $shape['expectedFirst'],
        ];
    }

    /**
     * `<...> (<ACTUAL>) <...> <anchor> <...> (<EXPECTED>) <...>`.
     *
     * @return null|array{string, string, string, string, string, bool}
     */
    private function splitBracketedPair(string $body, string $period, string $anchor): ?array
    {
        $anchorPosition = $this->singleTopLevelPosition($body, $anchor);

        if (null === $anchorPosition) {
            return null;
        }

        $groups = $this->topLevelParenthesesGroups($body);

        if (2 !== \count($groups)) {
            return null;
        }

        [$firstStart, $firstEnd] = $groups[0];
        [$secondStart, $secondEnd] = $groups[1];

        if ($firstEnd > $anchorPosition || $secondStart < $anchorPosition) {
            return null;
        }

        $first = substr($body, $firstStart + 1, $firstEnd - $firstStart - 1);
        $second = substr($body, $secondStart + 1, $secondEnd - $secondStart - 1);

        return [
            substr($body, 0, $firstStart + 1),
            $first,
            substr($body, $firstEnd, $secondStart + 1 - $firstEnd),
            $second,
            substr($body, $secondEnd).$period,
            false,
        ];
    }

    /**
     * Positions of the non-empty parentheses groups that are not nested inside another bracket.
     *
     * @return list<array{int, int}> [position of the `(`, position of the matching `)`]
     */
    private function topLevelParenthesesGroups(string $haystack): array
    {
        $groups = [];
        $stack = [];
        $closingBrackets = array_flip(self::BRACKET_PAIRS);
        $length = \strlen($haystack);
        $openPosition = null;

        for ($i = 0; $i < $length; ++$i) {
            $character = $haystack[$i];

            if (isset(self::BRACKET_PAIRS[$character])) {
                if ([] === $stack && '(' === $character) {
                    $openPosition = $i;
                }

                $stack[] = $character;

                continue;
            }

            if (isset($closingBrackets[$character]) && [] !== $stack && end($stack) === $closingBrackets[$character]) {
                array_pop($stack);

                if ([] === $stack && null !== $openPosition) {
                    if ($i > $openPosition + 1) {
                        $groups[] = [$openPosition, $i];
                    }

                    $openPosition = null;
                }
            }
        }

        return $groups;
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

            // A needle starting with the bracket that closes the innermost group still counts as
            // top level, so that shapes such as `(<type>) does not accept ...` can be located.
            $closesToTopLevel = 1 === \count($stack)
                && isset($closingBrackets[$character])
                && end($stack) === $closingBrackets[$character];

            if (([] === $stack || $closesToTopLevel) && substr($haystack, $i, $needleLength) === $needle) {
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
}
