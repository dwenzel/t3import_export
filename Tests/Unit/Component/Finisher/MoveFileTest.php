<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Component\Finisher;

use CPSIT\T3importExport\Resource\FileTargetGuard;
use CPSIT\ImportExportCore\Exception\InvalidArgumentException;
use CPSIT\T3importExport\Component\Finisher\MoveFile;
use CPSIT\ImportExportCore\LoggingInterface;
use CPSIT\ImportExportCore\Messaging\MessageContainer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;

class MoveFileTest extends TestCase
{
    protected MoveFile $subject;
    protected ResourceFactory|MockObject $resourceFactory;
    protected ResourceStorage|MockObject $resourceStorage;
    protected Folder|MockObject $folder;
    protected MessageContainer|MockObject $messageContainer;

    protected function setUp(): void
    {
        $this->resourceStorage = $this->createMock(ResourceStorage::class);

        $this->folder = $this->createMock(Folder::class);
        $this->resourceStorage->method('getDefaultFolder')
            ->willReturn($this->folder);

        $this->resourceFactory = $this->createMock(ResourceFactory::class);
        $this->resourceFactory->method('getDefaultStorage')
            ->willReturn($this->resourceStorage);

        $this->messageContainer = $this->createMock(MessageContainer::class);

        $this->subject = new MoveFile($this->resourceFactory, $this->messageContainer);
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

    /**
     * @return array<string, array{string|null, DuplicationBehavior}>
     */
    public static function conflictModeDataProvider(): array
    {
        return [
            'not configured' => [null, DuplicationBehavior::RENAME],
            MoveFile::CONFLICT_MODE_CANCEL => [MoveFile::CONFLICT_MODE_CANCEL, DuplicationBehavior::CANCEL],
            MoveFile::CONFLICT_MODE_RENAME_NEW_FILE => [MoveFile::CONFLICT_MODE_RENAME_NEW_FILE, DuplicationBehavior::RENAME],
            MoveFile::CONFLICT_MODE_OVERRIDE_EXISTING_FILE => [MoveFile::CONFLICT_MODE_OVERRIDE_EXISTING_FILE, DuplicationBehavior::REPLACE],
            'unknown' => ['foo', DuplicationBehavior::RENAME],
        ];
    }

    #[DataProvider('conflictModeDataProvider')]
    public function testProcessMapsConflictModeToDuplicationBehavior(?string $conflictMode, DuplicationBehavior $expected): void
    {
        $records = [];
        $result = [];
        $configuration = [
            'source' => ['name' => 'foo.xml'],
            'target' => ['name' => 'bar.xml'],
        ];
        if ($conflictMode !== null) {
            $configuration['target']['conflictMode'] = $conflictMode;
        }
        $file = $this->createStub(File::class);
        $this->resourceStorage->method('hasFileInFolder')->willReturn(true);
        $this->folder->method('getFiles')->willReturn(['foo.xml' => $file]);
        $this->resourceStorage->expects($this->once())
            ->method('moveFile')
            ->with($file, $this->folder, 'bar.xml', $expected);

        $this->assertTrue($this->subject->process($configuration, $records, $result));
    }

    public function testProcessDoesNotMoveToDeniedFileNames(): void
    {
        $records = [];
        $result = [];
        $file = $this->createStub(File::class);
        $this->resourceStorage->method('hasFileInFolder')->willReturn(true);
        $this->folder->method('getFiles')->willReturn(['foo.xml' => $file]);
        $this->resourceStorage->expects($this->never())->method('moveFile');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(FileTargetGuard::ERROR_FILE_TYPE_NOT_ALLOWED);

        $this->subject->process(
            ['source' => ['name' => 'foo.xml'], 'target' => ['name' => '.htaccess']],
            $records,
            $result
        );
    }
}
