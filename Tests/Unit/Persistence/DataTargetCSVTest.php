<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Persistence;

use CPSIT\ImportExportCore\Domain\Model\TaskResult;
use CPSIT\T3importExport\Persistence\DataTargetCSV;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Describes the behaviour of DataTargetCSV before it moves to the core
 * (CPS-IT/import-export-core#25)
 */
#[CoversClass(DataTargetCSV::class)]
class DataTargetCSVTest extends TestCase
{
    protected const string BOM = "\xEF\xBB\xBF";

    protected DataTargetCSV $subject;
    protected string $fileName = '';
    protected string $absoluteFileName = '';

    protected function setUp(): void
    {
        $this->subject = new DataTargetCSV();
        $this->fileName = 'typo3temp/var/tests/data-target-csv-' . uniqid('', true) . '.csv';
        $this->absoluteFileName = GeneralUtility::getFileAbsFileName($this->fileName);
        GeneralUtility::mkdir_deep(dirname($this->absoluteFileName));
    }

    protected function tearDown(): void
    {
        if (is_file($this->absoluteFileName)) {
            unlink($this->absoluteFileName);
        }
    }

    protected function createResult(array $records): TaskResult
    {
        $result = new TaskResult();
        $result->setElements($records);

        return $result;
    }

    public static function isConfigurationValidDataProvider(): iterable
    {
        yield 'empty' => [[], true];
        yield 'fields string' => [['fields' => 'uid,title'], true];
        yield 'fields empty' => [['fields' => ''], false];
        yield 'fields not a string' => [['fields' => ['uid']], false];
        yield 'delimiter one character' => [['delimiter' => ';'], true];
        yield 'delimiter too long' => [['delimiter' => ';;'], false];
        yield 'delimiter empty' => [['delimiter' => ''], false];
        yield 'enclosure not a string' => [['enclosure' => 1], false];
        yield 'escape one character' => [['escape' => '\\'], true];
        yield 'escape too long' => [['escape' => '\\\\'], false];
    }

    #[Test]
    #[DataProvider('isConfigurationValidDataProvider')]
    public function isConfigurationValidReturnsCorrectValues(array $configuration, bool $expected): void
    {
        self::assertSame($expected, $this->subject->isConfigurationValid($configuration));
    }

    #[Test]
    public function persistAllReturnsFalseForEmptyResult(): void
    {
        self::assertFalse($this->subject->persistAll(null, ['file' => $this->fileName]));
        self::assertFalse($this->subject->persistAll([], ['file' => $this->fileName]));
        self::assertFileDoesNotExist($this->absoluteFileName);
    }

    #[Test]
    public function persistAllWritesRecordsWithByteOrderMarkAndWithoutHeader(): void
    {
        $records = [
            ['uid' => 1, 'title' => 'foo'],
            ['uid' => 2, 'title' => 'bar baz'],
        ];

        $returned = $this->subject->persistAll($this->createResult($records), ['file' => $this->fileName]);

        self::assertSame($records, $returned);
        self::assertSame(
            self::BOM . "1,foo\n2,\"bar baz\"\n",
            file_get_contents($this->absoluteFileName)
        );
    }

    #[Test]
    public function persistAllWritesHeaderAndOnlyConfiguredFields(): void
    {
        $records = [
            ['uid' => 1, 'title' => 'foo', 'hidden' => 0],
            ['uid' => 2, 'title' => 'bar', 'hidden' => 1],
        ];

        $this->subject->persistAll(
            $this->createResult($records),
            ['file' => $this->fileName, 'fields' => 'uid, title']
        );

        self::assertSame(
            self::BOM . "uid,title\n1,foo\n2,bar\n",
            file_get_contents($this->absoluteFileName)
        );
    }

    #[Test]
    public function persistAllUsesFieldsOfFirstRecordWithoutFieldConfiguration(): void
    {
        $records = [
            ['uid' => 1, 'title' => 'foo'],
            ['uid' => 2, 'title' => 'bar', 'hidden' => 1],
        ];

        $this->subject->persistAll($this->createResult($records), ['file' => $this->fileName]);

        self::assertSame(
            self::BOM . "1,foo\n2,bar\n",
            file_get_contents($this->absoluteFileName)
        );
    }

    #[Test]
    public function persistAllUsesDelimiterAndEnclosure(): void
    {
        $records = [
            ['uid' => 1, 'title' => 'foo;bar'],
        ];

        $this->subject->persistAll(
            $this->createResult($records),
            ['file' => $this->fileName, 'delimiter' => ';', 'enclosure' => '\'']
        );

        self::assertSame(
            self::BOM . "1;'foo;bar'\n",
            file_get_contents($this->absoluteFileName)
        );
    }

    #[Test]
    public function persistDoesNothing(): void
    {
        self::assertNull($this->subject->persist(['uid' => 1], ['file' => $this->fileName]));
        self::assertFileDoesNotExist($this->absoluteFileName);
    }
}
