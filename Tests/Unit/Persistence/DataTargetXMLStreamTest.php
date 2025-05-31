<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Persistence;

use CPSIT\T3importExport\Domain\Model\DataStream;
use CPSIT\T3importExport\Domain\Model\DataStreamInterface;
use CPSIT\T3importExport\Domain\Model\Dto\FileInfo;
use CPSIT\ImportExportCore\Domain\Model\TaskResult;
use CPSIT\T3importExport\Persistence\DataTargetFileStream;
use CPSIT\T3importExport\Persistence\DataTargetXMLStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Resource\Exception\FileOperationErrorException;
use TYPO3\CMS\Core\Utility\File\BasicFileUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use XMLWriter;

/**
 * Testable version of DataTargetXMLStream that allows dependency injection
 */
class TestableDataTargetXMLStream extends DataTargetXMLStream
{
    private ?BasicFileUtility $testFileUtility = null;
    private ?FileInfo $testFileInfo = null;

    public function setTestFileUtility(BasicFileUtility $fileUtility): void
    {
        $this->testFileUtility = $fileUtility;
    }

    public function setTestFileInfo(FileInfo $fileInfo): void
    {
        $this->testFileInfo = $fileInfo;
    }

    #[\Override]
    protected function createTempFile($fileName): string
    {
        if ($this->testFileUtility !== null) {
            // Use injected file utility for testing
            $absPath = GeneralUtility::getFileAbsFileName(static::TEMP_DIRECTORY);
            if (!file_exists($absPath)) {
                @mkdir($absPath, 0777, true);
            }
            return $this->testFileUtility->getUniqueName($fileName, $absPath);
        }

        return parent::createTempFile($fileName);
    }

    #[\Override]
    protected function createAnonymTempFile(): string
    {
        // Fix the type issue by casting time() to string
        return $this->createTempFile(md5(uniqid((string)time(), true)));
    }

    #[\Override]
    public function persistAll($result = null, ?array $configuration = null)
    {
        if (
            !is_null($result)
            && $result instanceof TaskResult
        ) {
            $result->rewind();
            if ($result->valid()) {
                // Use test FileInfo if available
                $fileInfo = $this->testFileInfo ?? GeneralUtility::makeInstance(FileInfo::class, $this->tempFile);
                $result->setInfo($fileInfo);
            }
        }

        // Call the XMLStream specific persistAll logic
        if (isset($this->writer)) {
            if ($this->existTemplate($configuration)) {
                $this->writeXMLEndTemplateBased($configuration);
            } else {
                $this->writer->endElement();
            }
            $this->writer->flush();
            unset($this->writer);
        }
    }
}

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
 */
#[CoversClass(\CPSIT\T3importExport\Persistence\DataTargetFileStream::class)]
class DataTargetXMLStreamTest extends TestCase
{
    protected DataTargetXMLStream $subject;

    /**
     * @var BasicFileUtility|MockObject
     */
    protected BasicFileUtility $fileUtility;

    /**
     * @var \XMLWriter|MockObject
     */
    protected \XMLWriter $xmlWriter;

    /**
     * @var PersistenceManagerInterface|MockObject
     */
    protected PersistenceManagerInterface $persistenceManager;

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
     * Creates a mock XML writer
     */
    protected function mockXmlWriter(): void
    {
        $this->xmlWriter = $this->getMockBuilder(\XMLWriter::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Creates a mock persistence manager
     */
    protected function mockPersistenceManager(): void
    {
        $this->persistenceManager = $this->createMock(PersistenceManagerInterface::class);
        if (method_exists($this, 'injectPersistenceManager')) {
            $this->subject->injectPersistenceManager($this->persistenceManager);
        }
    }

    /**
     * Set up
     * @noinspection ReturnTypeCanBeDeclaredInspection
     */
    protected function setUp(): void
    {
        $this->mockBasicFileUtility();
        $this->mockPersistenceManager();
        $this->mockXmlWriter();

        $this->subject = new DataTargetXMLStream(
            null,
            $this->persistenceManager
        );
    }

    /**
     * @outputBuffering enabled
     * @throws FileOperationErrorException
     */
    #[Test]
    public function testPersistDataStreamInTaskResultIteratorWithDirectOutput(): void
    {
        $taskResult = new TaskResult();
        $taskResult->setElements(
            [
                $this->createDataStreamWithSampleBuffer('<a>b</a>'),
                $this->createDataStreamWithSampleBuffer('<a>b</a>'),
                $this->createDataStreamWithSampleBuffer('<a>b</a>'),
                $this->createDataStreamWithSampleBuffer('<a>b</a>'),
            ]
        );

        $config = [
            'flush' => true,
        ];

        /** @var DataStreamInterface $streamObject */
        foreach ($taskResult as $streamObject) {
            $this->subject->persist($streamObject, $config);
            $this->assertNull($streamObject->getStreamBuffer());
        }

        $this->subject->persistAll($taskResult, $config);
        $this->expectOutputString('<?xml version="1.0" encoding="UTF-8"?><rows><a>b</a><a>b</a><a>b</a><a>b</a></rows>');
    }

    public function createDataStreamWithSampleBuffer($buffer): DataStream
    {
        $ds = new DataStream();
        $ds->setStreamBuffer($buffer);
        return $ds;
    }

    /**
     * @outputBuffering enabled
     */
    #[Test]
    public function testPersistDataStreamInTaskResultIteratorWithDirectOutputAndCustomConfig(): void
    {
        $taskResult = new TaskResult();
        $taskResult->setElements(
            [
                $this->createDataStreamWithSampleBuffer('<a>b</a>'),
                $this->createDataStreamWithSampleBuffer('<a>b</a>'),
                $this->createDataStreamWithSampleBuffer('<a>b</a>'),
                $this->createDataStreamWithSampleBuffer('<a>b</a>'),
            ]
        );

        $config = [
            'rootNodeName' => 'test',
            'header' => '<xml myheader="123">',
            'flush' => true,
        ];

        /** @var DataStreamInterface $streamObject */
        foreach ($taskResult as $streamObject) {
            $this->subject->persist($streamObject, $config);
            $this->assertNull($streamObject->getStreamBuffer());
        }

        $this->subject->persistAll($taskResult, $config);
        $this->expectOutputString($config['header'] . '<test><a>b</a><a>b</a><a>b</a><a>b</a></test>');
    }

    #[Test]
    public function testPersistDataSteamXMLInTaskResultIteratorWithFileOutput(): void
    {
        // Create testable subject instance
        $testableSubject = new TestableDataTargetXMLStream(
            null,
            $this->persistenceManager
        );

        $taskResult = new TaskResult();
        $taskResult->setElements(
            [
                $this->createDataStreamWithSampleBuffer('<a>b</a>'),
                $this->createDataStreamWithSampleBuffer('<a>b</a>'),
                $this->createDataStreamWithSampleBuffer('<a>b</a>'),
                $this->createDataStreamWithSampleBuffer('<a>b</a>'),
            ]
        );

        $config = [
            'rootNodeName' => 'test',
            'header' => '<xml myheader="123">',
            'flush' => true,
            'output' => 'file',
        ];

        $absPath = GeneralUtility::getFileAbsFileName('typo3temp/test_mock_' . uniqid('', true));
        $tmpPath = $absPath . '/' . uniqid('', true);
        @mkdir($absPath, 0777, true);
        $this->fileUtility->expects($this->once())
            ->method('getUniqueName')
            ->willReturn($tmpPath);

        /** @var FileInfo $mockFileInfo */
        $mockFileInfo = new FileInfo($tmpPath);

        // Inject mocked dependencies into testable subject
        $testableSubject->setTestFileUtility($this->fileUtility);
        $testableSubject->setTestFileInfo($mockFileInfo);

        /** @var DataStreamInterface $streamObject */
        foreach ($taskResult as $streamObject) {
            $testableSubject->persist($streamObject, $config);
            $this->assertNull($streamObject->getStreamBuffer());
        }

        $testableSubject->persistAll($taskResult);
        $this->assertInstanceOf(
            FileInfo::class,
            $taskResult->getInfo()
        );

        $this->assertFileExists($tmpPath);

        $content = file_get_contents($tmpPath);
        $this->assertEquals($config['header'] . '<test><a>b</a><a>b</a><a>b</a><a>b</a></test>', $content);

        unlink($tmpPath);
        rmdir($absPath);
    }
}
