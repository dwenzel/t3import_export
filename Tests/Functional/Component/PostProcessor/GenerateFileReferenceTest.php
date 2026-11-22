<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Functional\Component\PostProcessor;

use CPSIT\T3importExport\Component\PostProcessor\GenerateFileReference;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

#[CoversClass(GenerateFileReference::class)]
class GenerateFileReferenceTest extends FunctionalTestCase
{
    // typo3/cms-install is required by t3extension_tools
    protected array $coreExtensionsToLoad = ['install'];

    protected array $testExtensionsToLoad = [
        't3extension_tools',
        't3import_export',
    ];

    protected GenerateFileReference $subject;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/sys_file.csv');
        $this->subject = $this->get(GenerateFileReference::class);
    }

    private function fetchReferences(): array
    {
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('sys_file_reference');
        $queryBuilder->getRestrictions()->removeAll();

        return $queryBuilder->select('pid', 'uid_local', 'uid_foreign', 'tablenames', 'fieldname')
            ->from('sys_file_reference')
            ->executeQuery()
            ->fetchAllAssociative();
    }

    private function configuration(array $additional = []): array
    {
        return array_replace(
            [
                'sourceField' => 'imageId',
                'targetField' => 'image',
                'identityField' => 'uid',
                'tableName' => 'tt_content',
                'targetPage' => 5,
            ],
            $additional
        );
    }

    #[Test]
    public function processCreatesFileReferenceRecordForArray(): void
    {
        $convertedRecord = ['uid' => 10];
        $record = ['imageId' => '1'];
        $configuration = $this->configuration(['fieldName' => 'image']);

        self::assertTrue($this->subject->process($configuration, $convertedRecord, $record));

        self::assertSame(1, $convertedRecord['image']);
        self::assertSame(
            [['pid' => 5, 'uid_local' => 1, 'uid_foreign' => 10, 'tablenames' => 'tt_content', 'fieldname' => 'image']],
            $this->fetchReferences()
        );
    }

    #[Test]
    public function processDoesNotCreateExistingFileReferenceAgain(): void
    {
        $configuration = $this->configuration(['fieldName' => 'image']);
        $record = ['imageId' => 1];
        $convertedRecord = ['uid' => 10];
        $this->subject->process($configuration, $convertedRecord, $record);
        $convertedRecord = ['uid' => 10];

        self::assertFalse($this->subject->process($configuration, $convertedRecord, $record));
        self::assertCount(1, $this->fetchReferences());
    }

    #[Test]
    public function processDoesNotCreateExistingFileReferenceWithoutFieldNameAgain(): void
    {
        $configuration = $this->configuration();
        $record = ['imageId' => 1];
        $convertedRecord = ['uid' => 10];
        $this->subject->process($configuration, $convertedRecord, $record);
        $convertedRecord = ['uid' => 10];

        self::assertFalse($this->subject->process($configuration, $convertedRecord, $record));
        self::assertCount(1, $this->fetchReferences());
    }

    #[Test]
    public function processReturnsFalseIfFileDoesNotExist(): void
    {
        $convertedRecord = ['uid' => 10];
        $record = ['imageId' => 2];

        self::assertFalse($this->subject->process($this->configuration(), $convertedRecord, $record));
        self::assertSame([], $this->fetchReferences());
    }

    #[Test]
    public function processReturnsFalseForArrayWithoutIdentity(): void
    {
        $convertedRecord = ['title' => 'New record'];
        $record = ['imageId' => 1];

        self::assertFalse($this->subject->process($this->configuration(), $convertedRecord, $record));
        self::assertArrayNotHasKey('image', $convertedRecord);
        self::assertSame([], $this->fetchReferences());
    }
}
