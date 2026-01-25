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
        $nonIgnorableCounter = 0;

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

            // Count non-ignorable errors excluding ignore.unmatched
            if (!$error->canBeIgnored() && $identifier !== self::IDENTIFIER_IGNORE_UNMATCHED) {
                ++$nonIgnorableCounter;
            }
        }

        arsort($errorCounter);

        $output->writeLineFormatted('📈 Error Identifier Summary:');
        $output->writeLineFormatted('────────────────────────────');

        foreach ($errorCounter as $identifier => $count) {
            $fileCount = \count($files[$identifier]);
            $suffix = $this->getFileSuffix($fileCount);
            $note = $identifier === self::IDENTIFIER_IGNORE_UNMATCHED
                ? ', can be removed after baseline update'
                : '';

            $output->writeLineFormatted(\sprintf(
                '  %d  %s <fg=gray>(in %d %s%s)</>',
                $count,
                $identifier,
                $fileCount,
                $suffix,
                $note
            ));
        }

        $totalFileCount = \count($uniqueFiles);
        $suffix = $this->getFileSuffix($totalFileCount);
        $output->writeLineFormatted('');
        $output->writeLineFormatted('📊 Summary:');
        $output->writeLineFormatted('───────────');

        $totalErrors = $analysisResult->getTotalErrorsCount();
        $output->writeLineFormatted(\sprintf('❌ Found <fg=red>%d</> errors', $totalErrors));

        $unmatchedCount = $errorCounter[self::IDENTIFIER_IGNORE_UNMATCHED] ?? 0;

        $treeItems = [];

        $noIdentifierCount = $errorCounter[self::IDENTIFIER_NO_IDENTIFIER] ?? 0;

        if ($unmatchedCount > 0) {
            $toFixCount = $totalErrors - $unmatchedCount;
            $treeItems[] = \sprintf('%d %s to fix', $toFixCount, $this->getErrorSuffix($toFixCount));
            $treeItems[] = \sprintf('%d %s can be removed from baseline', $unmatchedCount, $this->getErrorSuffix($unmatchedCount));
        }

        if ($noIdentifierCount > 0) {
            $treeItems[] = \sprintf('%d %s have no identifier', $noIdentifierCount, $this->getErrorSuffix($noIdentifierCount));
        }

        if ($nonIgnorableCounter > 0) {
            $treeItems[] = \sprintf('%d %s cannot be ignored by baseline', $nonIgnorableCounter, $this->getErrorSuffix($nonIgnorableCounter));
        }

        $lastIndex = \count($treeItems) - 1;
        foreach ($treeItems as $index => $item) {
            $prefix = $index === $lastIndex ? '└─' : '├─';
            $output->writeLineFormatted(\sprintf('   %s %s', $prefix, $item));
        }

        $output->writeLineFormatted(\sprintf('🏷️ In <fg=red>%d</> error identifiers', \count($errorCounter)));
        $output->writeLineFormatted(\sprintf('📂 Across <fg=red>%d</> %s', $totalFileCount, $suffix));
    }

    private function getFileSuffix(int $count): string
    {
        return 1 === $count ? 'file' : 'files';
    }

    private function getErrorSuffix(int $count): string
    {
        return 1 === $count ? 'error' : 'errors';
    }
}
