<?php declare(strict_types=1);

namespace Yamadashy\PhpStanFriendlyFormatter;

use PHPStan\Analyser\Error;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\ErrorFormatter\ErrorFormatter;
use PHPStan\Command\Output;
use PHPStan\File\RelativePathHelper;
use Yamadashy\PhpStanFriendlyFormatter\Config\FriendlyFormatterConfig;
use Yamadashy\PhpStanFriendlyFormatter\ErrorFormat\ErrorWriter;
use Yamadashy\PhpStanFriendlyFormatter\ErrorFormat\SummaryWriter;

class FriendlyErrorFormatter implements ErrorFormatter
{
    /** @var RelativePathHelper */
    private $relativePathHelper;

    /** @var FriendlyFormatterConfig */
    private $config;

    public function __construct(RelativePathHelper $relativePathHelper, int $lineBefore, int $lineAfter, ?string $editorUrl)
    {
        $this->relativePathHelper = $relativePathHelper;
        $this->config = new FriendlyFormatterConfig(
            $lineBefore,
            $lineAfter,
            $editorUrl
        );
    }

    /**
     * @return int error code
     */
    public function formatErrors(AnalysisResult $analysisResult, Output $output): int
    {
        if (!$analysisResult->hasErrors() && !$analysisResult->hasWarnings()) {
            return $this->handleNoErrors($output);
        }

        $output->writeLineFormatted('');

        $errorWriter = new ErrorWriter($this->relativePathHelper, $this->config);
        $errorWriter->writeFileSpecificErrors($analysisResult, $output);
        $errorWriter->writeNotFileSpecificErrors($analysisResult, $output);
        $errorWriter->writeWarnings($analysisResult, $output);

        $summaryWriter = new SummaryWriter();
        $summaryWriter->writeGroupedErrorsSummary($analysisResult, $output);

        $this->writeAnalysisResultMessage($analysisResult, $output);

        return 1;
    }

    private function handleNoErrors(Output $output): int
    {
        $output->getStyle()->success('No errors');

        return 0;
    }

    private function writeAnalysisResultMessage(AnalysisResult $analysisResult, Output $output): void
    {
        $warningsCount = \count($analysisResult->getWarnings());
        $finalMessage = \sprintf(1 === $analysisResult->getTotalErrorsCount() ? 'Found %d error' : 'Found %d errors', $analysisResult->getTotalErrorsCount());

        if ($warningsCount > 0) {
            $finalMessage .= \sprintf(1 === $warningsCount ? ' and %d warning' : ' and %d warnings', $warningsCount);
        }

        if ($analysisResult->getTotalErrorsCount() > 0) {
            $output->getStyle()->error($finalMessage);
        } else {
            $output->getStyle()->warning($finalMessage);
        }
    }
}
