<?php declare(strict_types=1);

namespace Yamadashy\PhpStanFriendlyFormatter\ErrorFormat;

use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;

class SummaryWriter
{
    private const IDENTIFIER_NO_IDENTIFIER = '<no-identifier>';

    public function writeGroupedErrorsSummary(AnalysisResult $analysisResult, Output $output): void
    {
        /** @var array<string, int> $errorCounter */
        $errorCounter = [];

        foreach ($analysisResult->getFileSpecificErrors() as $error) {
            $identifier = $error->getIdentifier() ?? self::IDENTIFIER_NO_IDENTIFIER;
            if (!\array_key_exists($identifier, $errorCounter)) {
                $errorCounter[$identifier] = 0;
            }
            ++$errorCounter[$identifier];
        }

        arsort($errorCounter);

        $output->writeLineFormatted('📊 Error Identifier Summary:');
        $output->writeLineFormatted('────────────────────────────');

        foreach ($errorCounter as $identifier => $count) {
            $output->writeLineFormatted(\sprintf('  %d  %s', $count, $identifier));
        }
    }
}
