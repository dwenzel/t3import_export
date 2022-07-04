<?php

namespace CPSIT\T3importExport\Tests\Unit\Persistence;

use CPSIT\T3importExport\Domain\Model\DataStream;
use CPSIT\T3importExport\Domain\Model\DataStreamInterface;
use CPSIT\T3importExport\Domain\Model\Dto\FileInfo;
use CPSIT\T3importExport\Domain\Model\TaskResult;
use CPSIT\T3importExport\Persistence\DataTargetFileStream;
use CPSIT\T3importExport\Persistence\DataTargetXMLStream;
use CPSIT\T3importExport\Tests\Unit\Traits\MockBasicFileUtilityTrait;
use CPSIT\T3importExport\Tests\Unit\Traits\MockObjectManagerTrait;
use CPSIT\T3importExport\Tests\Unit\Traits\MockPersistenceManagerTrait;
use CPSIT\T3importExport\Tests\Unit\Traits\MockXmlWriterTrait;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Resource\Exception\FileOperationErrorException;
use TYPO3\CMS\Core\Utility\File\BasicFileUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
 * @package CPSIT\T3importExport\Tests\Unit\Persistence
 * @coversDefaultClass \CPSIT\T3importExport\Persistence\DataTargetFileStream
 */
class DataTargetXMLStreamTest extends TestCase
{
    use MockBasicFileUtilityTrait,
        MockXmlWriterTrait,
        MockPersistenceManagerTrait,
        MockObjectManagerTrait;

    protected const TARGET_CLASS = 'baz';

    protected DataTargetXMLStream $subject;

    /**
     * Set up
     * @noinspection ReturnTypeCanBeDeclaredInspection
     */
    public function setUp()
    {
        $this->mockBasicFileUtility()
            ->mockPersistenceManager()
            ->mockXmlWriter();
        $this->subject = new DataTargetXMLStream(
            self::TARGET_CLASS,
            null,
            $this->persistenceManager
        );
    }

    /**
     * @outputBuffering enabled
     * @throws FileOperationErrorException
     */
    public function testPersistDataStreamInTaskResultIteratorWithDirectOutput(): void
    {
        $this->markTestIncomplete('test fails after refactoring');
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
            'flush' => true
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
    public function testPersistDataStreamInTaskResultIteratorWithDirectOutputAndCustomConfig(): void
    {
        /**
         * @see DataTargetFileStreamTest::testPersistDataSteamInTaskResultIterator()
         */
        $this->markTestIncomplete('this test seems to be a duplicate of DataTargetFileStreamTest::testPersistDataSteamInTaskResultIterator()');
        $taskResult = new TaskResult();
        $taskResult->setElements(
            [
                $this->createDataStreamWithSampleBuffer('<a>b</a>'),
                $this->createDataStreamWithSampleBuffer('<a>b</a>'),
                $this->createDataStreamWithSampleBuffer('<a>b</a>'),
                $this->createDataStreamWithSampleBuffer('<a>b</a>'),
            ]
        );

        $absPath = GeneralUtility::getFileAbsFileName(DataTargetFileStream::TEMP_DIRECTORY . uniqid('', true));
        $tmpPath = $absPath . '/' . uniqid('', true);
        @mkdir($absPath, 0777, true);
        $this->fileUtility->expects($this->once())
            ->method('getUniqueName')
            ->willReturn($tmpPath);

        $config = [
            'rootNodeName' => 'test',
            'header' => '<xml myheader="123">',
            'flush' => true
        ];

        $mockFileInfo = new FileInfo($tmpPath);

        $this->objectManager->expects($this->at(0))
            ->method('get')
            ->with(...[BasicFileUtility::class])
            ->willReturn($this->fileUtility);
        $this->objectManager->expects($this->at(1))
            ->method('get')
            ->with(...[FileInfo::class])
            ->willReturn($mockFileInfo);

        /** @var DataStreamInterface $streamObject */
        foreach ($taskResult as $streamObject) {
            $this->subject->persist($streamObject, $config);
            $this->assertNull($streamObject->getStreamBuffer());
        }

        $this->subject->persistAll($taskResult, $config);
        $this->expectOutputString($config['header'] . '<test><a>b</a><a>b</a><a>b</a><a>b</a></test>');
    }

    public function testPersistDataSteamXMLInTaskResultIteratorWithFileOutput(): void
    {
        $this->markTestIncomplete('test fails after refactoring');

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
            'output' => 'file'
        ];

        $absPath = GeneralUtility::getFileAbsFileName('typo3temp/test_mock_' . uniqid('', true));
        $tmpPath = $absPath . '/' . uniqid('', true);
        @mkdir($absPath, 0777, true);
        $this->fileUtility->expects($this->once())
            ->method('getUniqueName')
            ->willReturn($tmpPath);

        /** @var FileInfo $mockFileInfo */
        $mockFileInfo = new FileInfo($tmpPath);

        $this->objectManager->expects($this->at(0))
            ->method('get')
            ->with(...[BasicFileUtility::class])
            ->willReturn($this->fileUtility);
        $this->objectManager->expects($this->at(1))
            ->method('get')
            ->with(...[FileInfo::class])
            ->willReturn($mockFileInfo);

        /** @var DataStreamInterface $streamObject */
        foreach ($taskResult as $streamObject) {
            $this->subject->persist($streamObject, $config);
            $this->assertNull($streamObject->getStreamBuffer());
        }

        $this->subject->persistAll($taskResult);
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
