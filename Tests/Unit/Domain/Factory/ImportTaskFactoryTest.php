<?php
namespace CPSIT\T3import\Tests\Domain\Factory;

use CPSIT\T3import\Domain\Factory\AbstractFactory;
use CPSIT\T3import\Domain\Factory\ImportTaskFactory;
use CPSIT\T3import\Domain\Model\ImportTask;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/***************************************************************
 *  Copyright notice
 *  (c) 2015 Dirk Wenzel <dirk.wenzel@cps-it.de>
 *  All rights reserved
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Class ImportTaskFactoryTest
 *
 * @package CPSIT\T3import\Tests\Domain\Factory
 * @coversDefaultClass \CPSIT\T3import\Domain\Factory\ImportTaskFactory
 */
class ImportTaskFactoryTest extends UnitTestCase {

	/**
	 * @var \CPSIT\T3import\Domain\Factory\ImportTaskFactory
	 */
	protected $subject;

	public function setUp() {
		$this->subject = $this->getAccessibleMock(
			ImportTaskFactory::class, ['dummy'], [], '', FALSE
		);
	}

	/**
	 * @test
	 */
	public function getGetsImportTaskFromObjectManager() {
		$mockTask = $this->getMock(
			ImportTask::class
		);
		$settings = [];
		$identifier = 'foo';
		/** @var ObjectManager $mockObjectManager */
		$mockObjectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManager',
			['get'], [], '', FALSE);
		$mockObjectManager->expects($this->once())
			->method('get')
			->with(ImportTask::class)
			->will($this->returnValue($mockTask));
		$this->subject->injectObjectManager($mockObjectManager);

		$this->subject->get($identifier, $settings);
	}

	/**
	 * @test
	 */
	public function getGetsSetsIdentifier() {
		$mockTask = $this->getMock(
			ImportTask::class, ['setIdentifier']
		);
		$settings = [];
		$identifier = 'foo';
		/** @var ObjectManager $mockObjectManager */
		$mockObjectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManager',
			['get'], [], '', FALSE);
		$mockObjectManager->expects($this->once())
			->method('get')
			->with(ImportTask::class)
			->will($this->returnValue($mockTask));
		$this->subject->injectObjectManager($mockObjectManager);

		$mockTask->expects($this->once())
			->method('setIdentifier')
			->with($identifier);

		$this->subject->get($identifier, $settings);
	}

	/**
	 * @test
	 */
	public function getGetsSetsTargetClass() {
		$mockTask = $this->getMock(
			ImportTask::class, ['setTargetClass']
		);
		$targetClass = 'fooClassName';
		$settings = [
			'class' => $targetClass
		];
		$identifier = 'foo';
		/** @var ObjectManager $mockObjectManager */
		$mockObjectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManager',
			['get'], [], '', FALSE);
		$mockObjectManager->expects($this->once())
			->method('get')
			->with(ImportTask::class)
			->will($this->returnValue($mockTask));
		$this->subject->injectObjectManager($mockObjectManager);

		$mockTask->expects($this->once())
			->method('setTargetClass')
			->with($targetClass);

		$this->subject->get($identifier, $settings);
	}

	/**
	 * @test
	 */
	public function getGetsSetsDescription() {
		$mockTask = $this->getMock(
			ImportTask::class, ['setDescription']
		);
		$description = 'fooDescription';
		$settings = [
			'description' => $description
		];
		$identifier = 'foo';
		/** @var ObjectManager $mockObjectManager */
		$mockObjectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManager',
			['get'], [], '', FALSE);
		$mockObjectManager->expects($this->once())
			->method('get')
			->with(ImportTask::class)
			->will($this->returnValue($mockTask));
		$this->subject->injectObjectManager($mockObjectManager);

		$mockTask->expects($this->once())
			->method('setDescription')
			->with($description);

		$this->subject->get($identifier, $settings);
	}
}