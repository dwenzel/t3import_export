<?php
namespace CPSIT\T3importExport\Tests\Unit\Persistence;

use CPSIT\T3importExport\Domain\Model\DataStream;
use CPSIT\T3importExport\Domain\Model\DataStreamInterface;
use CPSIT\T3importExport\Domain\Model\TaskResult;
use CPSIT\T3importExport\Persistence\DataTargetFileStream;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\File\BasicFileUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;
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
class DataTargetFileSteamTest extends UnitTestCase {

	/**
	 * @var DataTargetFileStream|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
	 */
	protected $subject;

	/**
	 * Set up
	 */
	public function setUp()
	{
		$this->subject = $this->getAccessibleMock(
			DataTargetFileStream::class, ['dummy'], [], '', false
		);
	}

	public function createDataStreamWithSampleBuffer($buffer)
	{
		$ds = new DataStream();
		$ds->setSteamBuffer($buffer);
		return $ds;
	}

	/**
	 * @return \PHPUnit_Framework_MockObject_MockObject|ObjectManager
	 */
	protected function injectObjectManager()
	{
		/** @var ObjectManager $mockObjectManager */
		$mockObjectManager = $this->getMock(ObjectManager::class,
			[], [], '', FALSE);

		$this->subject->injectObjectManager($mockObjectManager);

		$this->assertSame(
			$mockObjectManager,
			$this->subject->_get('objectManager')
		);

		return $mockObjectManager;
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

		$absPath = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName('typo3temp/test_mock_'.uniqid());
		$tmpPath = $absPath . '/' . uniqid();
		@mkdir($absPath,0777,true);
		$mockedFileUtility->expects($this->once())
			->method('getUniqueName')
			->will($this->returnValue($tmpPath));

		$mockedObjectManager = $this->injectObjectManager();
		$mockedObjectManager->expects($this->once())
			->method('get')
			->with(BasicFileUtility::class)
			->will($this->returnValue($mockedFileUtility));

		/** @var DataStreamInterface $streamObject */
		foreach ($taskResult as $streamObject) {
			$this->subject->persist($streamObject, ['flush' => true]);
			$this->assertNull($streamObject->getSteamBuffer());
		}

		$this->subject->persistAll($taskResult);
		$path = $taskResult->getInfo();
		$this->assertEquals($tmpPath, $path);
		$this->assertFileExists($path);

		$content = file_get_contents($path);
		$this->assertEquals('aaaaaaabbbbbbbcccccccddddddd', $content);

		unlink($tmpPath);
		rmdir($absPath);
	}
}
