<?php

declare(strict_types=1);

namespace Swis\PHP_CodeSniffer\Reporter;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Reports\Report;
use Swis\Bitbucket\Reports\BitbucketApiClient;

class BitbucketReporter implements Report
{
    private const REPORT_TITLE = 'PHP_CodeSniffer';

    private BitbucketApiClient $apiClient;

    private string $separator;

    public function __construct()
    {
        $this->apiClient = new BitbucketApiClient();
        $this->separator = md5(self::class);
    }

    public function generateFileReport($report, File $phpcsFile, $showSources = false, $width = 80)
    {
        foreach ($report['messages'] as $line => $lineErrors) {
            foreach ($lineErrors as $colErrors) {
                foreach ($colErrors as $error) {
                    $issue = [
                        'summary' => $error['message'],
                        'filePath' => $phpcsFile->getFilename(),
                        'line' => $line,
                    ];

                    echo json_encode($issue, JSON_THROW_ON_ERROR).$this->separator;
                }
            }
        }

        return true;
    }

    public function generate(
        $cachedData,
        $totalFiles,
        $totalErrors,
        $totalWarnings,
        $totalFixable,
        $showSources = false,
        $width = 80,
        $interactive = false,
        $toScreen = true
    ) {
        $annotations = array_map(
            static fn ($issue) => json_decode($issue, false, 512, JSON_THROW_ON_ERROR),
            array_filter(explode($this->separator, $cachedData))
        );

        $reportUuid = $this->apiClient->createReport(self::REPORT_TITLE, $totalErrors + $totalWarnings);

        foreach ($annotations as $annotation) {
            $this->apiClient->addAnnotation(
                $reportUuid,
                $annotation->summary,
                $annotation->filePath,
                $annotation->line
            );
        }
    }
}
