<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Component\Finisher;

use CPSIT\T3importExport\Component\Finisher\MoveFile;
use CPSIT\ImportExportCore\LoggingInterface;
use CPSIT\ImportExportCore\Messaging\MessageContainer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Resource\ResourceStorage;

#[CoversClass(MoveFile::class)]
class MoveFileTest extends TestCase
{
    protected MoveFile $subject;
    protected StorageRepository&Stub $storageRepository;
    protected ResourceStorage&Stub $resourceStorage;
    protected Folder&Stub $folder;
    protected MessageContainer&Stub $messageContainer;

    protected function setUp(): void
    {
        $this->resourceStorage = $this->createStub(ResourceStorage::class);

        $this->folder = $this->createStub(Folder::class);
        $this->resourceStorage->method('getDefaultFolder')
            ->willReturn($this->folder);

        $this->storageRepository = $this->createStub(StorageRepository::class);
        $this->storageRepository->method('getDefaultStorage')
            ->willReturn($this->resourceStorage);

        $this->messageContainer = $this->createStub(MessageContainer::class);

        $this->subject = new MoveFile($this->storageRepository, $this->messageContainer);
    }

    public function testInstanceImplementsLoggingInterface(): void
    {
        $this->assertInstanceOf(
            LoggingInterface::class,
            $this->subject
        );
    }

    public function testGetErrorCodesReturnsClassConstant(): void
    {
        $this->assertSame(
            MoveFile::ERROR_CODES,
            $this->subject->getErrorCodes()
        );
    }

    public function testGetNoticeCodesReturnsClassConstant(): void
    {
        $this->assertSame(
            MoveFile::NOTICE_CODES,
            $this->subject->getNoticeCodes()
        );
    }

    public static function invalidConfigurationDataProvider(): array
    {
        return [
            'empty configuration' => [[]],
            'target name missing' => [['source' => ['name' => 'a.csv']]],
            'target name not a string' => [['target' => ['name' => 5], 'source' => ['name' => 'a.csv']]],
            'source name missing' => [['target' => ['name' => 'b.csv']]],
            'source name not a string' => [['target' => ['name' => 'b.csv'], 'source' => ['name' => ['a']]]],
            'target storage not an integer' => [
                ['target' => ['name' => 'b.csv', 'storage' => 'foo'], 'source' => ['name' => 'a.csv']],
            ],
            'source storage not an integer' => [
                ['target' => ['name' => 'b.csv'], 'source' => ['name' => 'a.csv', 'storage' => 'foo']],
            ],
            'target directory not a string' => [
                ['target' => ['name' => 'b.csv', 'directory' => [1]], 'source' => ['name' => 'a.csv']],
            ],
            'source directory not a string' => [
                ['target' => ['name' => 'b.csv'], 'source' => ['name' => 'a.csv', 'directory' => 3]],
            ],
            'conflict mode empty' => [
                ['target' => ['name' => 'b.csv', 'conflictMode' => ''], 'source' => ['name' => 'a.csv']],
            ],
            'conflict mode unknown' => [
                ['target' => ['name' => 'b.csv', 'conflictMode' => 'foo'], 'source' => ['name' => 'a.csv']],
            ],
        ];
    }

    #[DataProvider('invalidConfigurationDataProvider')]
    public function testIsConfigurationValidReturnsFalseForInvalidConfiguration(array $configuration): void
    {
        $this->assertFalse($this->subject->isConfigurationValid($configuration));
    }

    public static function validConfigurationDataProvider(): array
    {
        return [
            'minimal' => [['target' => ['name' => 'b.csv'], 'source' => ['name' => 'a.csv']]],
            'complete' => [
                [
                    'target' => [
                        'name' => 'b.csv',
                        'storage' => '2',
                        'directory' => 'archive/',
                        'conflictMode' => MoveFile::CONFLICT_MODE_OVERRIDE_EXISTING_FILE,
                    ],
                    'source' => ['name' => 'a.csv', 'storage' => 1, 'directory' => 'import/'],
                ],
            ],
        ];
    }

    #[DataProvider('validConfigurationDataProvider')]
    public function testIsConfigurationValidReturnsTrueForValidConfiguration(array $configuration): void
    {
        $this->assertTrue($this->subject->isConfigurationValid($configuration));
    }
}
