<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Persistence;

use TYPO3\CMS\Core\Resource\Exception\FileOperationErrorException;
use CPSIT\T3importExport\Domain\Model\TaskResult;
use CPSIT\T3importExport\Exception\InvalidArgumentException;
use CPSIT\T3importExport\Persistence\DataTargetCSV;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DataTargetCSVTest extends TestCase
{
    protected const BOM = "\xEF\xBB\xBF";

    protected DataTargetCSV $subject;

    protected function setUp(): void
    {
        $this->subject = new DataTargetCSV();
        GeneralUtility::mkdir_deep(GeneralUtility::getFileAbsFileName('typo3temp/var/tests'));
    }

    protected function createTaskResult(array $records): TaskResult
    {
        $result = new TaskResult();
        $result->setElements($records);

        return $result;
    }

    /**
     * @test
     */
    public function persistAllWritesFileInAllowedDirectory(): void
    {
        foreach (['typo3temp/var/tests', 'fileadmin'] as $directory) {
            $fileName = $directory . '/data-target-csv-' . uniqid('', true) . '.csv';
            $absoluteFileName = GeneralUtility::getFileAbsFileName($fileName);
            GeneralUtility::mkdir_deep(dirname($absoluteFileName));
            try {
                $this->subject->persistAll($this->createTaskResult([['uid' => 1]]), ['file' => $fileName]);

                self::assertSame(self::BOM . "1\n", file_get_contents($absoluteFileName));
            } finally {
                @unlink($absoluteFileName);
            }
        }
    }

    public static function deniedFileDataProvider(): iterable
    {
        yield 'php file' => ['typo3temp/var/tests/data-target-csv.php'];
        yield 'phtml file' => ['typo3temp/var/tests/data-target-csv.phtml'];
        yield 'php before csv' => ['typo3temp/var/tests/data-target-csv.php.csv'];
        yield 'htaccess' => ['typo3temp/var/tests/.htaccess'];
        yield 'user.ini' => ['typo3temp/var/tests/.user.ini'];
        yield 'public directory' => ['data-target-csv.csv'];
        yield 'dot dot segment' => ['typo3temp/var/tests/../../data-target-csv.csv'];
        yield 'stream wrapper' => ['php://filter/resource=typo3temp/var/tests/data-target-csv.csv'];
    }

    /**
     * @test
     * @dataProvider deniedFileDataProvider
     */
    public function persistAllDoesNotWriteDeniedFiles(string $fileName): void
    {
        $absoluteFileName = GeneralUtility::getFileAbsFileName($fileName);
        $exception = null;
        try {
            $this->subject->persistAll($this->createTaskResult([['uid' => 1]]), ['file' => $fileName]);
        } catch (InvalidArgumentException $exception) {
        }
        self::assertInstanceOf(InvalidArgumentException::class, $exception);
        if ($absoluteFileName !== '') {
            self::assertFileDoesNotExist($absoluteFileName);
        }
    }

    /**
     * @test
     */
    public function persistAllThrowsExceptionIfFileCannotBeOpened(): void
    {
        $directory = 'typo3temp/var/tests/data-target-csv-' . uniqid('', true) . '.csv';
        $absoluteDirectory = GeneralUtility::getFileAbsFileName($directory);
        GeneralUtility::mkdir_deep($absoluteDirectory);
        try {
            $this->expectException(FileOperationErrorException::class);

            @$this->subject->persistAll($this->createTaskResult([['uid' => 1]]), ['file' => $directory]);
        } finally {
            GeneralUtility::rmdir($absoluteDirectory);
        }
    }
}
