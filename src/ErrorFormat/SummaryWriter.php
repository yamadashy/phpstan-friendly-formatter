<?php declare(strict_types=1);

namespace Yamadashy\PhpStanFriendlyFormatter\ErrorFormat;

use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;

class SummaryWriter
{
    private const IDENTIFIER_NO_IDENTIFIER = '<no-identifier>';
    private const IDENTIFIER_IGNORE_UNMATCHED = 'ignore.unmatched';

    public function writeGroupedErrorsSummary(AnalysisResult $analysisResult, Output $output): void
    {
        /** @var array<string, int> $errorCounter */
        $errorCounter = [];
        $nonignorableCounter = 0;

        /** @var array<string, array<string, true>> $files files per identifier */
        $files = [];

        /** @var array<string, true> $uniqueFiles */
        $uniqueFiles = [];

        foreach ($analysisResult->getFileSpecificErrors() as $error) {
            $identifier = $error->getIdentifier() ?? self::IDENTIFIER_NO_IDENTIFIER;
            $file = $error->getFile();

            $errorCounter[$identifier] ??= 0;
            ++$errorCounter[$identifier];

            $files[$identifier][$file] = true;

            $uniqueFiles[$file] = true;

            if (!$error->canBeIgnored()) {
                ++$nonignorableCounter;
            }
        }

        arsort($errorCounter);

        $output->writeLineFormatted('📊 Error Identifier Summary:');
        $output->writeLineFormatted('────────────────────────────');

        foreach ($errorCounter as $identifier => $count) {
            $fileCount = \count($files[$identifier]);
            $suffix = $this->getFileSuffix($fileCount);
            $color = self::IDENTIFIER_IGNORE_UNMATCHED === $identifier ? 'green' : 'red';

            $output->writeLineFormatted(\sprintf(
                "  <fg={$color}>%d</>  <fg=yellow>%s</> (in <fg={$color}>%d</> %s)",
                $count,
                $identifier,
                $fileCount,
                $suffix
            ));
        }

        $totalFileCount = \count($uniqueFiles);
        $suffix = $this->getFileSuffix($totalFileCount);
        $output->writeLineFormatted('');
        $output->writeLineFormatted('📊 Summary:');
        $output->writeLineFormatted('───────────');

        $output->writeLineFormatted(\sprintf('❌ Found <fg=red>%d</> errors', $analysisResult->getTotalErrorsCount()));
        $output->writeLineFormatted(\sprintf('🏷️  In <fg=red>%d</> error categories', \count($errorCounter)));
        $output->writeLineFormatted(\sprintf('📂 Across <fg=red>%d</> %s', $totalFileCount, $suffix));

        if (isset($errorCounter[self::IDENTIFIER_IGNORE_UNMATCHED]) || isset($errorCounter[self::IDENTIFIER_NO_IDENTIFIER]) || 0 !== $nonignorableCounter) {
            $output->writeLineFormatted('');
            $output->writeLineFormatted('ℹ️  Note:');
            $output->writeLineFormatted('────────');
        }

        if (isset($errorCounter[self::IDENTIFIER_IGNORE_UNMATCHED])) {
            $output->writeLineFormatted(\sprintf('🎉 <fg=green>%d</> errors can be removed after updating the baseline.', $errorCounter[self::IDENTIFIER_IGNORE_UNMATCHED]));
        }

        if (isset($errorCounter[self::IDENTIFIER_NO_IDENTIFIER])) {
            $output->writeLineFormatted(\sprintf('⚠️  <fg=yellow>%d</> errors have no identifier. Consider upgrading to PHPStan v2, which requires identifiers.', $errorCounter[self::IDENTIFIER_NO_IDENTIFIER]));
        }

        if (0 !== $nonignorableCounter) {
            $output->writeLineFormatted(\sprintf('🚨 <fg=red>%d</> errors cannot be ignored by baseline!', $nonignorableCounter));
        }
    }

    private function getFileSuffix(int $count): string
    {
        return 1 === $count ? 'file' : 'files';
    }
}
