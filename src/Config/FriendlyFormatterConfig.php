<?php declare(strict_types=1);

namespace Yamadashy\PhpStanFriendlyFormatter\Config;

class FriendlyFormatterConfig
{
    /** @var int */
    public $lineBefore;

    /** @var int */
    public $lineAfter;

    /** @var null|string */
    public $editorUrl;

    public function __construct(
        int $lineBefore,
        int $lineAfter,
        ?string $editorUrl
    ) {
        if ($lineBefore < 0) {
            throw new \InvalidArgumentException('lineBefore must be a non-negative integer.');
        }

        if ($lineAfter < 0) {
            throw new \InvalidArgumentException('lineAfter must be a non-negative integer.');
        }

        $this->lineBefore = $lineBefore;
        $this->lineAfter = $lineAfter;
        $this->editorUrl = $editorUrl;
    }
}
