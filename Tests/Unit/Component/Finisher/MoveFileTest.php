<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Component\Finisher;

use CPSIT\T3importExport\Component\Finisher\MoveFile;
use CPSIT\T3importExport\LoggingInterface;
use CPSIT\T3importExport\Messaging\MessageContainer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
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
}
