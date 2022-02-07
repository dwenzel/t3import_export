<?php

namespace CPSIT\T3importExport\Tests\Unit\Persistence;

use CPSIT\T3importExport\Domain\Model\DataStream;
use CPSIT\T3importExport\Domain\Model\DataStreamInterface;
use CPSIT\T3importExport\Domain\Model\Dto\FileInfo;
use CPSIT\T3importExport\Domain\Model\TaskResult;
use CPSIT\T3importExport\Persistence\DataTargetFileStream;
use CPSIT\T3importExport\Tests\Unit\Traits\MockBasicFileUtilityTrait;
use CPSIT\T3importExport\Tests\Unit\Traits\MockPersistenceManagerTrait;
use PHPUnit\Framework\TestCase;
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
class DataTargetFileStreamTest extends TestCase
{
    use MockBasicFileUtilityTrait,
        MockPersistenceManagerTrait;

    protected const TARGET_CLASS = 'foo';

    protected DataTargetFileStream $subject;

    /**
     * Set up
     * @noinspection ReturnTypeCanBeDeclaredInspection
     */
    public function setUp()
    {
        $this->mockPersistenceManager();
        $this->subject = new DataTargetFileStream(
            self::TARGET_CLASS,
            null,
            $this->persistenceManager
        );
        $this->mockBasicFileUtility();
    }

    public function testPersistDataSteamInTaskResultIterator(): void
    {
        $this->markTestSkipped('should rewrite it mocking file access');
        $taskResult = new TaskResult();
        $taskResult->setElements(
            [
                $this->createDataStreamWithSampleBuffer('aaaaaaa'),
                $this->createDataStreamWithSampleBuffer('bbbbbbb'),
                $this->createDataStreamWithSampleBuffer('ccccccc'),
                $this->createDataStreamWithSampleBuffer('ddddddd'),
            ]
        );


        $absPath = GeneralUtility::getFileAbsFileName(DataTargetFileStream::TEMP_DIRECTORY . uniqid('', true));
        $tmpPath = $absPath . '/' . uniqid('', true);
        @mkdir($absPath, 0777, true);
        $this->fileUtility->expects($this->once())
            ->method('getUniqueName')
            ->willReturn($tmpPath);
        $mockFileInfo = new FileInfo($tmpPath);

        /** @var DataStreamInterface $streamObject */
        foreach ($taskResult as $streamObject) {
            $this->subject->persist($streamObject, ['flush' => true]);
            $this->assertNull($streamObject->getStreamBuffer());
        }

        $this->subject->persistAll($taskResult);

        $this->assertSame(
            $mockFileInfo,
            $taskResult->getInfo()
        );
        $this->assertFileExists($tmpPath);

        $content = file_get_contents($tmpPath);
        /** @noinspection SpellCheckingInspection */
        $this->assertEquals('aaaaaaabbbbbbbcccccccddddddd', $content);

        unlink($tmpPath);
        rmdir($absPath);
    }

    public function createDataStreamWithSampleBuffer($buffer): DataStream
    {
        $ds = new DataStream();
        $ds->setStreamBuffer($buffer);
        return $ds;
    }
}
