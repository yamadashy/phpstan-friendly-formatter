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
            $suffix = 1 === $fileCount ? 'file' : 'files';
            $color = 'ignore.unmatched' === $identifier ? 'green' : 'red';
            $output->writeLineFormatted(\sprintf(
                "  <fg={$color}>%d</>  <fg=yellow>%s</> (in <fg={$color}>%d</> %s)",
                $count,
                $identifier,
                $fileCount,
                $suffix
            ));
        }

        $totalFileCount = \count($uniqueFiles);
        $suffix = 1 === $totalFileCount ? 'file' : 'files';

        $output->writeLineFormatted('');
        $output->writeLineFormatted('📊 Summary:');
        $output->writeLineFormatted('───────────');

        $output->writeLineFormatted("❌ Found <fg=red>{$analysisResult->getTotalErrorsCount()}</> errors");
        $output->writeLineFormatted('🏷️  In <fg=red>'.\count($errorCounter).'</> error categories');
        $output->writeLineFormatted("📂 Across <fg=red>{$totalFileCount}</> {$suffix}");

        if (isset($errorCounter['ignore.unmatched']) || isset($errorCounter[self::IDENTIFIER_NO_IDENTIFIER]) || 0 !== $nonignorableCounter) {
            $output->writeLineFormatted('');
            $output->writeLineFormatted('ℹ️  Note:');
            $output->writeLineFormatted('────────');
        }

        if (isset($errorCounter['ignore.unmatched'])) {
            $output->writeLineFormatted("🎉 <fg=green>{$errorCounter['ignore.unmatched']}</> errors can be removed after updating the baseline.");
        }

        if (isset($errorCounter[self::IDENTIFIER_NO_IDENTIFIER])) {
            $output->writeLineFormatted("⚠️  <fg=yellow>{$errorCounter[self::IDENTIFIER_NO_IDENTIFIER]}</> errors have no identifier. Consider upgrading to PHPStan v2, which requires identifiers.");
        }

        if (0 !== $nonignorableCounter) {
            $output->writeLineFormatted("🚨 <fg=red>{$nonignorableCounter}</> errors cannot be ignored by baseline!");
        }
    }
}
