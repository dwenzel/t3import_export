<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Persistence;

use CPSIT\T3importExport\Domain\Model\DataStream;
use CPSIT\T3importExport\Domain\Model\DataStreamInterface;
use CPSIT\T3importExport\Domain\Model\Dto\FileInfo;
use CPSIT\ImportExportCore\Domain\Model\TaskResult;
use CPSIT\T3importExport\Persistence\DataTargetFileStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use TYPO3\CMS\Core\Resource\Exception\FileOperationErrorException;
use TYPO3\CMS\Core\Utility\File\BasicFileUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2015 Dirk Wenzel <dirk.wenzel@cps-it.de>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Class DataTargetRepositoryTest
 *
 * @coversDefaultClass \CPSIT\T3importExport\Persistence\DataTargetFileStream
 */
#[CoversClass(DataTargetFileStream::class)]
class DataTargetFileStreamTest extends TestCase
{

    protected DataTargetFileStream $subject;

    /**
     * @var BasicFileUtility|MockObject
     */
    protected BasicFileUtility|MockObject $fileUtility;

    /**
     * @var PersistenceManagerInterface|MockObject
     */
    protected PersistenceManagerInterface|MockObject $persistenceManager;

    /**
     * Creates a mock basic file utility
     */
    protected function mockBasicFileUtility(): void
    {
        $this->fileUtility = $this->getMockBuilder(BasicFileUtility::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getUniqueName'])
            ->getMock();
    }

    /**
     * Creates a mock persistence manager
     */
    protected function mockPersistenceManager(): void
    {
        $this->persistenceManager = $this->createMock(PersistenceManagerInterface::class);
    }

    protected function setUp(): void
    {
        $this->mockPersistenceManager();
        $this->subject = new DataTargetFileStream(
            null,
            $this->persistenceManager
        );
        $this->mockBasicFileUtility();
    }

    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    #[Test]
    public function testPersistDataStreamInTaskResultIterator(): void
    {
        $taskResult = new TaskResult();
        $taskResult->setElements(
            [
                $this->createDataStreamWithSampleBuffer('aaaaaaa'),
                $this->createDataStreamWithSampleBuffer('bbbbbbb'),
                $this->createDataStreamWithSampleBuffer('ccccccc'),
                $this->createDataStreamWithSampleBuffer('ddddddd'),
            ]
        );

        // Create a testable version of DataTargetFileStream to mock file operations
        $subject = $this->getMockBuilder(DataTargetFileStream::class)
            ->setConstructorArgs([null, $this->persistenceManager])
            ->onlyMethods(['writeBuffer'])
            ->getMock();

        $mockTempFile = '/fake/temp/file/path/test.tmp';

        // Track the buffers written to verify concatenation
        $writtenBuffers = [];
        $subject->expects($this->exactly(4))
            ->method('writeBuffer')
            ->willReturnCallback(function ($buffer) use (&$writtenBuffers, $mockTempFile, $subject) {
                $writtenBuffers[] = $buffer;
                // Set the tempFile property using reflection since it's protected
                $reflection = new ReflectionClass($subject);
                $tempFileProperty = $reflection->getProperty('tempFile');
                $tempFileProperty->setAccessible(true);
                $tempFileProperty->setValue($subject, $mockTempFile);
            });

        /** @var DataStreamInterface $streamObject */
        foreach ($taskResult as $streamObject) {
            $subject->persist($streamObject, ['flush' => true]);
            $this->assertNull($streamObject->getStreamBuffer());
        }

        $subject->persistAll($taskResult);

        // Verify that all buffers were written in correct order
        /** @noinspection SpellCheckingInspection */
        $this->assertEquals(['aaaaaaa', 'bbbbbbb', 'ccccccc', 'ddddddd'], $writtenBuffers);

        // Verify that TaskResult contains FileInfo with correct path
        $fileInfo = $taskResult->getInfo();
        $this->assertInstanceOf(FileInfo::class, $fileInfo);
        $this->assertEquals($mockTempFile, $fileInfo->getPathname());
    }

    #[Test]
    public function persistAppendsBuffersToTemporaryFileAndPersistAllSetsFileInfo(): void
    {
        $taskResult = new TaskResult();
        $taskResult->setElements(
            [
                $this->createDataStreamWithSampleBuffer('<a>1</a>'),
                $this->createDataStreamWithSampleBuffer('<a>2</a>'),
            ]
        );

        foreach ($taskResult as $streamObject) {
            $this->subject->persist($streamObject, ['flush' => true]);
            self::assertNull($streamObject->getStreamBuffer());
        }
        $this->subject->persistAll($taskResult);

        $fileInfo = $taskResult->getInfo();
        self::assertInstanceOf(FileInfo::class, $fileInfo);
        $fileName = $fileInfo->getPathname();
        self::assertStringStartsWith(
            GeneralUtility::getFileAbsFileName(DataTargetFileStream::TEMP_DIRECTORY),
            $fileName
        );
        self::assertSame('<a>1</a><a>2</a>', file_get_contents($fileName));
        unlink($fileName);
    }

    #[Test]
    public function persistKeepsBufferWithoutFlush(): void
    {
        $stream = $this->createDataStreamWithSampleBuffer('<a>1</a>');
        $taskResult = new TaskResult();
        $taskResult->setElements([$stream]);

        $this->subject->persist($stream, []);
        $this->subject->persistAll($taskResult);

        self::assertSame('<a>1</a>', $stream->getStreamBuffer());
        unlink($taskResult->getInfo()->getPathname());
    }

    #[Test]
    public function persistIgnoresObjectsWithoutStream(): void
    {
        self::assertNull($this->subject->persist(['uid' => 1], ['flush' => true]));

        $taskResult = new TaskResult();
        $taskResult->setElements([['uid' => 1]]);
        $this->subject->persistAll($taskResult);

        // no file was written
        self::assertNull($taskResult->getInfo());
    }

    #[Test]
    public function persistAllDoesNotSetInfoForEmptyResult(): void
    {
        $taskResult = new TaskResult();

        $this->subject->persistAll($taskResult);
        $this->subject->persistAll(null);

        self::assertNull($taskResult->getInfo());
    }

    #[Test]
    public function persistCreatesNewTemporaryFileIfPreviousFileWasRemoved(): void
    {
        $files = [];
        try {
            $taskResult = new TaskResult();
            $taskResult->setElements([$this->createDataStreamWithSampleBuffer('<a>1</a>')]);

            $this->subject->persist($this->createDataStreamWithSampleBuffer('<a>1</a>'));
            $this->subject->persistAll($taskResult);
            $files[] = $firstFile = $taskResult->getInfo()->getPathname();
            unlink($firstFile);

            $this->subject->persist($this->createDataStreamWithSampleBuffer('<a>2</a>'));
            $this->subject->persistAll($taskResult);
            $files[] = $secondFile = $taskResult->getInfo()->getPathname();

            self::assertNotSame($firstFile, $secondFile);
            self::assertSame('<a>2</a>', file_get_contents($secondFile));
        } finally {
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }

    #[Test]
    public function persistCreatesTemporaryDirectoryIfMissing(): void
    {
        $directory = rtrim(GeneralUtility::getFileAbsFileName(DataTargetFileStream::TEMP_DIRECTORY), '/');
        $backup = '';
        if (file_exists($directory)) {
            $candidate = $directory . '_backup_' . bin2hex(random_bytes(4));
            if (!rename($directory, $candidate)) {
                self::markTestSkipped('The existing temporary directory could not be moved aside.');
            }
            $backup = $candidate;
        }

        $fileName = '';
        try {
            $taskResult = new TaskResult();
            $taskResult->setElements([$this->createDataStreamWithSampleBuffer('<a>1</a>')]);
            $this->subject->persist($this->createDataStreamWithSampleBuffer('<a>1</a>'));
            $this->subject->persistAll($taskResult);

            self::assertDirectoryExists($directory);
            $fileName = $taskResult->getInfo()->getPathname();
            self::assertSame($directory, dirname($fileName));
        } finally {
            if ($fileName !== '' && is_file($fileName)) {
                unlink($fileName);
            }
            if ($backup !== '') {
                foreach (glob($directory . '/*') ?: [] as $file) {
                    unlink($file);
                }
                if (is_dir($directory)) {
                    rmdir($directory);
                }
                rename($backup, $directory);
            }
        }
    }

    #[Test]
    public function persistUsesUniqueNameFromFileUtility(): void
    {
        $directory = sys_get_temp_dir() . '/t3import_export_' . bin2hex(random_bytes(6));
        mkdir($directory);
        $fileName = $directory . '/unique.tmp';
        try {
            $this->fileUtility->expects(self::once())
                ->method('getUniqueName')
                ->with(self::isString(), GeneralUtility::getFileAbsFileName(DataTargetFileStream::TEMP_DIRECTORY))
                ->willReturn($fileName);
            GeneralUtility::addInstance(BasicFileUtility::class, $this->fileUtility);

            $taskResult = new TaskResult();
            $taskResult->setElements([$this->createDataStreamWithSampleBuffer('<a>1</a>')]);
            $this->subject->persist($this->createDataStreamWithSampleBuffer('<a>1</a>'));
            $this->subject->persistAll($taskResult);

            self::assertSame($fileName, $taskResult->getInfo()->getPathname());
            self::assertSame('<a>1</a>', file_get_contents($fileName));
        } finally {
            if (is_file($fileName)) {
                unlink($fileName);
            }
            rmdir($directory);
        }
    }

    #[Test]
    public function persistThrowsExceptionIfTemporaryFileCannotBeCreated(): void
    {
        $fileName = sys_get_temp_dir() . '/t3import_export_missing_' . bin2hex(random_bytes(6)) . '/unique.tmp';
        $fileUtility = $this->createStub(BasicFileUtility::class);
        $fileUtility->method('getUniqueName')->willReturn($fileName);
        GeneralUtility::addInstance(BasicFileUtility::class, $fileUtility);

        $this->expectException(FileOperationErrorException::class);
        $this->expectExceptionMessage('can\'t create new temp file: \'' . $fileName . '\'');

        $this->persistWithoutWarnings($this->createDataStreamWithSampleBuffer('<a>1</a>'));
    }

    #[Test]
    public function persistThrowsExceptionIfBufferCannotBeWritten(): void
    {
        // a directory exists, but cannot be written as a file
        $directory = sys_get_temp_dir();
        (new ReflectionClass($this->subject))->getProperty('tempFile')->setValue($this->subject, $directory);

        $this->expectException(FileOperationErrorException::class);
        $this->expectExceptionMessage('can\'t write in temp file: \'' . $directory . '\'');

        $this->persistWithoutWarnings($this->createDataStreamWithSampleBuffer('<a>1</a>'));
    }

    /**
     * Persists a stream while the warnings of failing file functions are ignored
     */
    protected function persistWithoutWarnings(DataStream $stream): void
    {
        set_error_handler(static fn(): bool => true, E_WARNING);
        try {
            $this->subject->persist($stream);
        } finally {
            restore_error_handler();
        }
    }

    #[Test]
    public function isConfigurationValidReturnsTrue(): void
    {
        self::assertTrue($this->subject->isConfigurationValid([]));
    }

    public function createDataStreamWithSampleBuffer($buffer): DataStream
    {
        $ds = new DataStream();
        $ds->setStreamBuffer($buffer);
        return $ds;
    }
}
