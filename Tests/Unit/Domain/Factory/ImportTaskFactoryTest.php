<?php
namespace CPSIT\T3import\Tests\Domain\Factory;

use CPSIT\T3import\Factory\AbstractFactory;
use CPSIT\T3import\Domain\Factory\ImportTaskFactory;
use CPSIT\T3import\Domain\Model\ImportTask;
use CPSIT\T3import\Persistence\DataSourceInterface;
use CPSIT\T3import\Persistence\DataTargetInterface;
use CPSIT\T3import\Persistence\DataTargetRepository;
use CPSIT\T3import\Persistence\Factory\DataSourceFactory;
use CPSIT\T3import\Persistence\Factory\DataTargetFactory;
use CPSIT\T3import\Service\InvalidConfigurationException;
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
	public function injectDataSourceFactorySetsFactory() {
		$mockFactory = $this->getMock(
			DataSourceFactory::class
		);
		$this->subject->injectDataSourceFactory(
			$mockFactory
		);
		$this->assertAttributeEquals(
			$mockFactory,
			'dataSourceFactory',
			$this->subject
		);
	}

	/**
	 * @test
	 */
	public function injectDataTargetFactorySetsFactory() {
		$mockFactory = $this->getMock(
			DataTargetFactory::class
		);
		$this->subject->injectDataTargetFactory(
			$mockFactory
		);
		$this->assertAttributeEquals(
			$mockFactory,
			'dataTargetFactory',
			$this->subject
		);
	}

	/**
	 * @test
	 * @expectedException \CPSIT\T3import\Service\InvalidConfigurationException
	 */
	public function getGetsImportTaskFromObjectManager() {
		$identifier = 'foo';
		$mockTask = $this->getMock(
			ImportTask::class, []
		);
		$settings = [];
		/** @var ObjectManager $mockObjectManager */
		$mockObjectManager = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManager',
			['get'], [], '', FALSE);
		$mockObjectManager->expects($this->once())
			->method('get')
			->with(ImportTask::class)
			->will($this->returnValue($mockTask));
		$this->subject->injectObjectManager($mockObjectManager);

		$this->subject->get($settings, $identifier);
	}

	/**
	 * @test
	 * @expectedException \CPSIT\T3import\Service\InvalidConfigurationException
	 */
	public function getSetsIdentifier() {
		$identifier = 'foo';
		$mockTask = $this->getMock(
			ImportTask::class, ['setIdentifier']
		);
		$settings = [];
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
		$this->subject->get($settings, $identifier);
	}

	/**
	 * @test
	 * @expectedException \CPSIT\T3import\Service\InvalidConfigurationException
	 */
	public function getSetsTargetClass() {
		$identifier = 'foo';
		$mockTask = $this->getMock(
			ImportTask::class, ['setTargetClass']
		);
		$targetClass = 'fooClassName';
		$settings = [
			'class' => $targetClass
		];
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

		$this->subject->get($settings, $identifier);
	}

	/**
	 * @test
	 * @expectedException \CPSIT\T3import\Service\InvalidConfigurationException
	 */
	public function getSetsDescription() {
		$identifier = 'foo';
		$mockTask = $this->getMock(
			ImportTask::class, ['setDescription']
		);
		$description = 'fooDescription';
		$settings = [
			'description' => $description
		];
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

		$this->subject->get($settings, $identifier);
	}

	/**
	 * @test
	 */
	public function getSetsTarget() {
		$this->subject = $this->getAccessibleMock(
			ImportTaskFactory::class, ['setSource'], [], '', FALSE
		);
		$this->subject->expects($this->once())
			->method('setSource');
		$identifier = 'foo';
		$mockTask = $this->getMock(
			ImportTask::class, ['setTarget']
		);
		$settings = [
			'target' => [
				'identifier' => 'bar'
			]
		];
		/** @var ObjectManager $mockObjectManager */
		$mockObjectManager = $this->getMock(
			ObjectManager::class,
			['get'], [], '', FALSE);
		$mockObjectManager->expects($this->once())
			->method('get')
			->with(ImportTask::class)
			->will($this->returnValue($mockTask));
		$this->subject->injectObjectManager($mockObjectManager);
		$mockTarget = $this->getMock(DataTargetInterface::class);

		$mockTargetFactory = $this->getMock(
			DataTargetFactory::class, ['get']
		);
		$mockTargetFactory->expects($this->once())
			->method('get')
			->with($settings['target'])
			->will($this->returnValue($mockTarget));
		$this->subject->injectDataTargetFactory($mockTargetFactory);

		$mockTask->expects($this->once())
			->method('setTarget')
			->with($mockTarget);

		$this->subject->get($settings, $identifier);
	}

	/**
	 * @test
	 * @expectedException \CPSIT\T3import\Service\InvalidConfigurationException
	 * @expectedExceptionCode 1451052262
	 */
	public function getThrowsExceptionForMissingTarget() {
		$identifier = 'foo';
		$settings = ['foo'];
		$mockTask = $this->getMock(
			ImportTask::class, ['setTarget']
		);
		/** @var ObjectManager $mockObjectManager */
		$mockObjectManager = $this->getMock(
			ObjectManager::class,
			['get'], [], '', FALSE);
		$mockObjectManager->expects($this->once())
			->method('get')
			->with(ImportTask::class)
			->will($this->returnValue($mockTask));
		$this->subject->injectObjectManager($mockObjectManager);

		$this->subject->get($settings, $identifier);
	}

	/**
	 * @test
	 * @expectedException \CPSIT\T3import\Service\InvalidConfigurationException
	 * @expectedExceptionCode 1451206701
	 */
	public function getThrowsExceptionForMissingSource() {
		$this->subject = $this->getAccessibleMock(
			ImportTaskFactory::class, ['setTarget'], [], '', FALSE
		);
		$this->subject->expects($this->once())
			->method('setTarget');
		$identifier = 'foo';
		$settings = ['foo'];
		$mockTask = $this->getMock(
			ImportTask::class, ['setTarget']
		);
		/** @var ObjectManager $mockObjectManager */
		$mockObjectManager = $this->getMock(
			ObjectManager::class,
			['get'], [], '', FALSE);
		$mockObjectManager->expects($this->once())
			->method('get')
			->with(ImportTask::class)
			->will($this->returnValue($mockTask));
		$this->subject->injectObjectManager($mockObjectManager);

		$this->subject->get($settings, $identifier);
	}

	/**
	 * @test
	 */
	public function getSetsSource() {
		$this->subject = $this->getAccessibleMock(
			ImportTaskFactory::class, ['setTarget'], [], '', FALSE
		);
		$this->subject->expects($this->once())
			->method('setTarget');
		$identifier = 'foo';
		$mockTask = $this->getMock(
			ImportTask::class, ['setSource']
		);
		$settings = [
			'source' => [
				'identifier' => 'bar'
			]
		];
		/** @var ObjectManager $mockObjectManager */
		$mockObjectManager = $this->getMock(
			ObjectManager::class,
			['get'], [], '', FALSE);
		$mockObjectManager->expects($this->once())
			->method('get')
			->with(ImportTask::class)
			->will($this->returnValue($mockTask));
		$this->subject->injectObjectManager($mockObjectManager);
		$mockSource = $this->getMock(
			DataSourceInterface::class
		);

		$mockSourceFactory = $this->getMock(
			DataSourceFactory::class, ['get']
		);
		$mockSourceFactory->expects($this->once())
			->method('get')
			->with($settings['source'])
			->will($this->returnValue($mockSource));
		$this->subject->injectDataSourceFactory($mockSourceFactory);

		$mockTask->expects($this->once())
			->method('setSource')
			->with($mockSource);

		$this->subject->get($settings, $identifier);
	}

}