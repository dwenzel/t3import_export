<?php
namespace CPSIT\T3importExport\Tests\Unit\Persistence;

use CPSIT\T3importExport\Domain\Model\DataStream;
use CPSIT\T3importExport\Domain\Model\DataStreamInterface;
use CPSIT\T3importExport\Domain\Model\Dto\FileInfo;
use CPSIT\T3importExport\Domain\Model\TaskResult;
use CPSIT\T3importExport\Persistence\DataTargetFileStream;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Utility\File\BasicFileUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

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

    /**
     * @var DataTargetFileStream|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
     */
    protected $subject;

    /**
     * @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectManager;

    /**
     * Set up
     */
    public function setUp()
    {
        $this->subject = $this->getAccessibleMock(
            DataTargetFileStream::class, ['dummy'], [], '', false
        );
        $this->objectManager = $this->getMockBuilder(ObjectManager::class)
            ->disableOriginalConstructor()->setMethods(['get'])->getMock();

        $this->subject->injectObjectManager($this->objectManager);
    }

    public function createDataStreamWithSampleBuffer($buffer)
    {
        $ds = new DataStream();
        $ds->setStreamBuffer($buffer);
        return $ds;
    }

    /**
     * @test
     */
    public function persistDataSteamInTaskResultIterator()
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

        $mockedFileUtility = $this->getAccessibleMock(
            BasicFileUtility::class,
            ['getUniqueName'],
            [],
            '',
            false
        );

        $absPath = GeneralUtility::getFileAbsFileName(DataTargetFileStream::TEMP_DIRECTORY.uniqid());
        $tmpPath = $absPath . '/' . uniqid();
        @mkdir($absPath, 0777, true);
        $mockedFileUtility->expects($this->once())
            ->method('getUniqueName')
            ->will($this->returnValue($tmpPath));
        /** @var FileInfo $mockFileInfo */
        $mockFileInfo = new FileInfo($tmpPath);

        $this->objectManager->expects($this->at(0))
            ->method('get')
            ->with(BasicFileUtility::class)
            ->will($this->returnValue($mockedFileUtility));
        $this->objectManager->expects($this->at(1))
            ->method('get')
            ->with(FileInfo::class)
            ->will($this->returnValue($mockFileInfo));

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
        $this->assertEquals('aaaaaaabbbbbbbcccccccddddddd', $content);

        unlink($tmpPath);
        rmdir($absPath);
    }
}
