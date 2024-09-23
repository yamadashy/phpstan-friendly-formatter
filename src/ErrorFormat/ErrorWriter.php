<?php declare(strict_types=1);

namespace Yamadashy\PhpStanFriendlyFormatter\ErrorFormat;

use PHPStan\Analyser\Error;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;
use PHPStan\File\RelativePathHelper;
use Yamadashy\PhpStanFriendlyFormatter\CodeHighlight\CodeHighlighter;
use Yamadashy\PhpStanFriendlyFormatter\Config\FriendlyFormatterConfig;

class ErrorWriter
{
    /** @var RelativePathHelper */
    private $relativePathHelper;

    /** @var FriendlyFormatterConfig */
    private $config;

    public function __construct(
        RelativePathHelper $relativePathHelper,
        FriendlyFormatterConfig $config
    ) {
        $this->relativePathHelper = $relativePathHelper;
        $this->config = $config;
    }

    public function writeFileSpecificErrors(AnalysisResult $analysisResult, Output $output): void
    {
        $codeHighlighter = new CodeHighlighter();
        $errorsByFile = [];

        foreach ($analysisResult->getFileSpecificErrors() as $error) {
            $filePath = $error->getTraitFilePath() ?? $error->getFilePath();
            $relativeFilePath = $this->relativePathHelper->getRelativePath($filePath);
            $errorsByFile[$relativeFilePath][] = $error;
        }

        foreach ($errorsByFile as $relativeFilePath => $errors) {
            $output->writeLineFormatted("❯ {$relativeFilePath}");
            $output->writeLineFormatted('--'.str_repeat('-', mb_strlen($relativeFilePath)));
            $output->writeLineFormatted('');

            foreach ($errors as $error) {
                $message = $error->getMessage();
                $tip = $this->getFormattedTip($error);
                $errorIdentifier = $error->getIdentifier();
                $filePath = $error->getTraitFilePath() ?? $error->getFilePath();
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
                    $codeSnippet = $codeHighlighter->highlight($fileContent, $line, $this->config->lineBefore, $this->config->lineAfter);
                }

                $output->writeLineFormatted("  <fg=red;options=bold>✘</> <fg=default;options=bold>{$message}</>");

                if (null !== $tip) {
                    $output->writeLineFormatted("  <fg=default>💡  {$tip}</>");
                }

                if (null !== $errorIdentifier) {
                    $output->writeLineFormatted("  <fg=default>🪪  {$errorIdentifier}</>");
                }

                if (\is_string($this->config->editorUrl)) {
                    $output->writeLineFormatted('  ✏️  '.str_replace(['%file%', '%line%'], [$error->getTraitFilePath() ?? $error->getFilePath(), (string) $error->getLine()], $this->config->editorUrl));
                }

                $output->writeLineFormatted($codeSnippet);
                $output->writeLineFormatted('');
            }
        }
    }

    public function writeNotFileSpecificErrors(AnalysisResult $analysisResult, Output $output): void
    {
        foreach ($analysisResult->getNotFileSpecificErrors() as $notFileSpecificError) {
            $output->writeLineFormatted("  <fg=red;options=bold>✘</> <fg=default;options=bold>{$notFileSpecificError}</>");
            $output->writeLineFormatted('');
        }
    }

    public function writeWarnings(AnalysisResult $analysisResult, Output $output): void
    {
        foreach ($analysisResult->getWarnings() as $warning) {
            $output->writeLineFormatted("  <fg=yellow;options=bold>⚠</> <fg=default;options=bold>{$warning}</>");
            $output->writeLineFormatted('');
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
