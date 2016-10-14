<?php
namespace CPSIT\T3importExport\Tests\Unit\Command;

use CPSIT\T3importExport\Command\ImportCommandController;
use CPSIT\T3importExport\Domain\Factory\ImportSetFactory;
use CPSIT\T3importExport\Domain\Factory\ImportTaskFactory;
use CPSIT\T3importExport\Domain\Model\Dto\DemandInterface;
use CPSIT\T3importExport\Domain\Model\ImportSet;
use CPSIT\T3importExport\Domain\Model\ImportTask;
use CPSIT\T3importExport\Service\DataTransferProcessor;
use CPSIT\T3importExport\Service\ImportProcessor;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2016 Dirk Wenzel <dirk.wenzel@cps-it.de>
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
 * Class ImportCommandControllerTest
 *
 * @package CPSIT\T3importExport\Tests\Unit\Command
 * @coversDefaultClass \CPSIT\T3importExport\Command\ImportCommandController
 */
class ImportCommandControllerTest extends UnitTestCase {

	/**
	 * @var ImportCommandController
	 */
	protected $subject;

	/**
	 * set up
	 */
	public function setUp() {
		$this->subject = $this->getAccessibleMock(
			ImportCommandController::class, ['dummy']
		);
	}

	/**
	 * @test
	 * @covers ::injectDataTransferProcessor
	 */
	public function injectinjectDataTransferProcessorForObjectSetsDataTransferProcessor() {
		$expectedProcessor = $this->getMock(
			DataTransferProcessor::class);
		$this->subject->injectDataTransferProcessor($expectedProcessor);

		$this->assertSame(
			$expectedProcessor,
			$this->subject->_get('importProcessor')
		);
	}

	/**
	 * @test
	 */
	public function injectImportTaskFactorySetsFactory() {
		$factory = $this->getMock(
			ImportTaskFactory::class
		);
		$this->subject->injectImportTaskFactory($factory);
		$this->assertSame(
			$factory,
			$this->subject->_get('importTaskFactory')
		);
	}

	/**
	 * @test
	 */
	public function injectImportSetFactorySetsFactory() {
		$factory = $this->getMock(
			ImportSetFactory::class
		);
		$this->subject->injectImportSetFactory($factory);
		$this->assertSame(
			$factory,
			$this->subject->_get('importSetFactory')
		);
	}

	/**
	 * @test
	 */
	public function injectConfigurationManagerSetsConfigurationManagerAndSettings() {
		$importProcessorSettings = ['foo'];
		$extbaseFrameWorkConfig = [
			'settings' => [
				'import' => $importProcessorSettings
			]
		];
		$configurationManager = $this->getAccessibleMock(
			ConfigurationManager::class,
			['getConfiguration']
		);
		$configurationManager->expects($this->once())
			->method('getConfiguration')
			->with(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK)
			->will($this->returnValue($extbaseFrameWorkConfig));
		$this->subject->injectConfigurationManager($configurationManager);

		$this->assertSame(
			$configurationManager,
			$this->subject->_get('configurationManager')
		);
		$this->assertSame(
			$importProcessorSettings,
			$this->subject->_get('settings')
		);
	}

	/**
	 * @test
	 * @covers ::taskCommand
	 */
	public function taskCommandBuildsAndProcessesQueue() {
		$identifier = 'foo';
		$settings = [
			'tasks' => [
				$identifier => ['bar']
			]
		];
		$this->subject->_set('settings', $settings);
		$mockTask = $this->getMock(
			ImportTask::class
		);
		$importTaskFactory = $this->getMock(
			ImportTaskFactory::class, ['get']
		);
		$importTaskFactory->expects($this->once())
			->method('get')
			->with($settings['tasks'][$identifier])
			->will($this->returnValue($mockTask));
		$this->subject->injectImportTaskFactory($importTaskFactory);

		$importProcessor = $this->getMock(
			DataTransferProcessor::class,
			['buildQueue', 'process'], [], '', FALSE
		);
		$task = 'foo';
		$result = ['bar'];
		$mockDemand = $this->getMock(
			DemandInterface::class
		);
		$importProcessor->expects($this->once())
			->method('buildQueue')
			->with($mockDemand);
		$importProcessor->expects($this->once())
			->method('process')
			->with($mockDemand)
			->will($this->returnValue($result));
		$this->subject->injectDataTransferProcessor($importProcessor);
		$mockObjectManager = $this->getMock(
			ObjectManager::class,
			['get']);
		$mockObjectManager->expects($this->once())
			->method('get')
			->will($this->returnValue($mockDemand));
		$this->subject->_set('objectManager', $mockObjectManager);

		$this->subject->taskCommand($task);
	}

	/**
	 * @test
	 * @covers ::setCommand
	 */
	public function setCommandBuildsAndProcessesQueue() {
		$identifier = 'foo';
		$settings = [
			'sets' => [
				$identifier => ['bar']
			]
		];
		$this->subject->_set('settings', $settings);
		$mockSet = $this->getMock(
			ImportSet::class, ['getTasks']
		);
		$mockSet->expects($this->once())
			->method('getTasks')
			->will($this->returnValue([]));
		$importSetFactory = $this->getMock(
			ImportSetFactory::class, ['get']
		);
		$importSetFactory->expects($this->once())
			->method('get')
			->with($settings['sets'][$identifier])
			->will($this->returnValue($mockSet));
		$this->subject->injectImportSetFactory($importSetFactory);

		$importProcessor = $this->getMock(
			DataTransferProcessor::class,
			['buildQueue', 'process'], [], '', FALSE
		);
		$set = 'foo';
		$result = ['bar'];
		$mockDemand = $this->getMock(
			DemandInterface::class
		);
		$importProcessor->expects($this->once())
			->method('buildQueue')
			->with($mockDemand);
		$importProcessor->expects($this->once())
			->method('process')
			->with($mockDemand)
			->will($this->returnValue($result));
		$this->subject->injectDataTransferProcessor($importProcessor);
		$mockObjectManager = $this->getMock(
			ObjectManager::class,
			['get']);
		$mockObjectManager->expects($this->once())
			->method('get')
			->will($this->returnValue($mockDemand));
		$this->subject->_set('objectManager', $mockObjectManager);

		$this->subject->setCommand($set);
	}

}
