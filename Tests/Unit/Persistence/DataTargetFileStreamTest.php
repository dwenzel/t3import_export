<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Persistence;

use CPSIT\T3importExport\Domain\Model\DataStream;
use CPSIT\T3importExport\Domain\Model\DataStreamInterface;
use CPSIT\T3importExport\Domain\Model\Dto\FileInfo;
use CPSIT\ImportExportCore\Domain\Model\TaskResult;
use CPSIT\T3importExport\Persistence\DataTargetFileStream;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use TYPO3\CMS\Core\Utility\File\BasicFileUtility;
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
class DataTargetFileStreamTest extends TestCase
{

    protected DataTargetFileStream $subject;

    /**
     * @var BasicFileUtility|MockObject
     */
    protected BasicFileUtility $fileUtility;

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

    public function createDataStreamWithSampleBuffer($buffer): DataStream
    {
        $ds = new DataStream();
        $ds->setStreamBuffer($buffer);
        return $ds;
    }
}
