<?php declare(strict_types=1);

namespace Yamadashy\PhpStanFriendlyFormatter;

use PHPStan\Analyser\Error;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\ErrorFormatter\ErrorFormatter;
use PHPStan\Command\Output;
use PHPStan\File\RelativePathHelper;

class FriendlyErrorFormatter implements ErrorFormatter
{
    /** @var RelativePathHelper */
    private $relativePathHelper;

    /** @var int */
    private $lineBefore;

    /** @var int */
    private $lineAfter;

    /** @var null|string */
    private $editorUrl;

    public function __construct(RelativePathHelper $relativePathHelper, int $lineBefore, int $lineAfter, ?string $editorUrl)
    {
        $this->relativePathHelper = $relativePathHelper;
        $this->lineBefore = $lineBefore;
        $this->lineAfter = $lineAfter;
        $this->editorUrl = $editorUrl;
    }

    /**
     * @return int error code
     */
    public function formatErrors(AnalysisResult $analysisResult, Output $output): int
    {
        if (!$analysisResult->hasErrors() && !$analysisResult->hasWarnings()) {
            $output->getStyle()->success('No errors');

            return 0;
        }

        $this->writeFileSpecificErrors($analysisResult, $output);
        $this->writeNotFileSpecificErrors($analysisResult, $output);
        $this->writeWarnings($analysisResult, $output);
        $this->writeGroupedErrorsSummary($analysisResult, $output);
        $this->writeFinalMessage($analysisResult, $output);

        return 1;
    }

    private function writeFileSpecificErrors(AnalysisResult $analysisResult, Output $output): void
    {
        $codeHighlighter = new CodeHighlighter();

        foreach ($analysisResult->getFileSpecificErrors() as $error) {
            $message = $error->getMessage();
            $tip = $this->getFormattedTip($error);
            $errorIdentifier = $error->getIdentifier();
            $filePath = $error->getTraitFilePath() ?? $error->getFilePath();
            $relativeFilePath = $this->relativePathHelper->getRelativePath($filePath);
            $line = $error->getLine();
            $fileContent = null;

            if (file_exists($filePath)) {
                $fileContent = (string) file_get_contents($filePath);
            }

            if (null === $fileContent) {
                $codeSnippet = '  <fg=#888><no such file></>';
            } elseif (null === $line) {
                $codeSnippet = '  <fg=#888><unknown file line></>';
            } else {
                $codeSnippet = $codeHighlighter->highlight($fileContent, $line, $this->lineBefore, $this->lineAfter);
            }

            $output->writeLineFormatted("  <fg=red;options=bold>✘</> <fg=default;options=bold>{$message}</>");

            if (null !== $tip) {
                $output->writeLineFormatted("  <fg=default>💡  {$tip}</>");
            }

            if (null !== $errorIdentifier) {
                $output->writeLineFormatted("  <fg=default>🪪  {$errorIdentifier}</>");
            }

            $output->writeLineFormatted("  at <fg=cyan>{$relativeFilePath}</>:<fg=cyan>{$line}</>");

            if (\is_string($this->editorUrl)) {
                $output->writeLineFormatted('  ✏️  '.str_replace(['%file%', '%line%'], [$error->getTraitFilePath() ?? $error->getFilePath(), (string) $error->getLine()], $this->editorUrl));
            }

            $output->writeLineFormatted($codeSnippet);
            $output->writeLineFormatted('');
        }
    }

    private function writeNotFileSpecificErrors(AnalysisResult $analysisResult, Output $output): void
    {
        foreach ($analysisResult->getNotFileSpecificErrors() as $notFileSpecificError) {
            $output->writeLineFormatted("  <fg=red;options=bold>✘</> <fg=default;options=bold>{$notFileSpecificError}</>");
            $output->writeLineFormatted('');
        }
    }

    private function writeWarnings(AnalysisResult $analysisResult, Output $output): void
    {
        foreach ($analysisResult->getWarnings() as $warning) {
            $output->writeLineFormatted("  <fg=yellow;options=bold>⚠</> <fg=default;options=bold>{$warning}</>");
            $output->writeLineFormatted('');
        }
    }

    private function writeGroupedErrorsSummary(AnalysisResult $analysisResult, Output $output): void
    {
        /** @var array<string, int> $errorCounter */
        $errorCounter = [];

        foreach ($analysisResult->getFileSpecificErrors() as $error) {
            $identifier = $error->getIdentifier() ?? 'unknown';
            if (!array_key_exists($identifier, $errorCounter)) {
                $errorCounter[$identifier] = 0;
            }
            $errorCounter[$identifier]++;
        }

        arsort($errorCounter);

        $output->writeLineFormatted('<fg=red;options=bold>Error Identifier Summary:</>');

        foreach ($errorCounter as $identifier => $count) {
            $output->writeLineFormatted(sprintf('  %d  %s', $count, $identifier));
        }
    }

    private function writeFinalMessage(AnalysisResult $analysisResult, Output $output): void
    {
        $warningsCount = \count($analysisResult->getWarnings());
        $finalMessage = sprintf(1 === $analysisResult->getTotalErrorsCount() ? 'Found %d error' : 'Found %d errors', $analysisResult->getTotalErrorsCount());

        if ($warningsCount > 0) {
            $finalMessage .= sprintf(1 === $warningsCount ? ' and %d warning' : ' and %d warnings', $warningsCount);
        }

        if ($analysisResult->getTotalErrorsCount() > 0) {
            $output->getStyle()->error($finalMessage);
        } else {
            $output->getStyle()->warning($finalMessage);
        }
    }

    private function getFormattedTip(Error $error): ?string
    {
        $tip = $error->getTip();

        if (null === $tip) {
            return null;
        }

        return implode("\n    ", explode("\n", $tip));
    }
}
