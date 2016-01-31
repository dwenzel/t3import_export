<?php
namespace CPSIT\T3import\Tests\Service;

use CPSIT\T3import\Component\Finisher\FinisherInterface;
use CPSIT\T3import\Domain\Model\Dto\ImportDemand;
use CPSIT\T3import\Domain\Model\ImportTask;
use CPSIT\T3import\Persistence\DataSourceInterface;
use CPSIT\T3import\Persistence\DataTargetInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use CPSIT\T3import\Component\PreProcessor\PreProcessorInterface;
use CPSIT\T3import\Service\ImportProcessor;
use TYPO3\CMS\Core\Tests\UnitTestCase;

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
 * Class ImportCommandControllerTest
 *
 * @package CPSIT\T3import\Tests\Service
 * @coversDefaultClass \CPSIT\T3import\Service\ImportProcessor
 */
class ImportProcessorTest extends UnitTestCase {

	/**
	 * @var \CPSIT\T3import\Service\ImportProcessor
	 */
	protected $subject;

	public function setUp() {
		$this->subject = $this->getAccessibleMock(
			ImportProcessor::class,
			['dummy'], [], '', FALSE);
	}

	/**
	 * @test
	 * @covers ::injectPersistenceManager
	 */
	public function injectPersistenceManagerForObjectSetsPersistenceManager() {
		/** @var PersistenceManager $mockPersistenceManager */
		$mockPersistenceManager = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\PersistenceManager',
			[], [], '', FALSE);

		$this->subject->injectPersistenceManager($mockPersistenceManager);

		$this->assertSame(
			$mockPersistenceManager,
			$this->subject->_get('persistenceManager')
		);
	}

	/**
	 * @test
	 * @covers ::getQueue
	 */
	public function getQueueForArrayReturnsInitiallyEmptyArray() {
		$this->assertSame(
			[],
			$this->subject->getQueue()
		);
	}

	/**
	 * @test
	 * @covers ::buildQueue
	 */
	public function buildQueueSetsQueue() {
		$identifier = 'bar';
		$mockTask = $this->getMock(
			ImportTask::class,
			['getSource', 'getIdentifier']
		);
		$mockDemand = $this->getMock(
			ImportDemand::class, ['getTasks']
		);
		$tasks = [$mockTask];
		$mockDemand->expects($this->once())
			->method('getTasks')
			->will($this->returnValue($tasks));

		$sourceConfig = ['baz'];
		$mockDataSource = $this->getMockForAbstractClass(
			DataSourceInterface::class
		);
		$mockDataSource->expects($this->once())
			->method('getConfiguration')
			->will($this->returnValue($sourceConfig));
		$mockTask->expects($this->once())
			->method('getSource')
			->will($this->returnValue($mockDataSource));
		$mockTask->expects($this->once())
			->method('getIdentifier')
			->will($this->returnValue($identifier));

		$mockResult = ['foo'];
		$mockDataSource->expects($this->once())
			->method('getRecords')
			->with($sourceConfig)
			->will($this->returnValue($mockResult)
			);

		$this->subject->buildQueue($mockDemand);
		$expectedQueue = [
			$identifier => $mockResult
		];

		$this->assertSame(
			$expectedQueue,
			$this->subject->getQueue()
		);
	}

	/**
	 * @test
	 */
	public function processInitiallyReturnsEmptyArray() {
		$importDemand = $this->getMock(
			ImportDemand::class, ['getTasks']
		);
		$importDemand->expects($this->any())
			->method('getTasks')
			->will($this->returnValue([]));

		$this->assertSame(
			[],
			$this->subject->process($importDemand)
		);
	}

	/**
	 * @test
	 */
	public function processDoesNotRunEmptyQueue() {
		$identifier = 'foo';
		$importDemand = $this->getMock(
			ImportDemand::class, ['getTasks']
		);
		$mockTask = $this->getMock(
			ImportTask::class, ['getIdentifier']
		);
		$importDemand->expects($this->any())
			->method('getTasks')
			->will($this->returnValue($mockTask));
		$mockTask->expects($this->any())
			->method('getIdentifier')
			->will($this->returnValue($identifier));

		$this->assertSame(
			[],
			$this->subject->process($importDemand)
		);
	}

	/**
	 * @test
	 */
	public function processPreProcesses() {
		$this->subject = $this->getAccessibleMock(
			ImportProcessor::class,
			['preProcessSingle']
		);

		$identifier = 'foo';
		$importDemand = $this->getMock(
			ImportDemand::class, ['getTasks']
		);
		$mockTask = $this->getMock(
			ImportTask::class, ['getIdentifier', 'getTarget']
		);
		$mockTarget = $this->getMock(
			DataTargetInterface::class
		);
		$importDemand->expects($this->any())
			->method('getTasks')
			->will($this->returnValue([$mockTask]));
		$mockTask->expects($this->any())
			->method('getIdentifier')
			->will($this->returnValue($identifier));
		$mockTask->expects($this->any())
			->method('getTarget')
			->will($this->returnValue($mockTarget));

		$mockPersistenceManager = $this->getAccessibleMock(
			PersistenceManager::class,
			['persistAll']
		);
		$this->subject->injectPersistenceManager($mockPersistenceManager);

		$singleRecord = ['foo' => 'bar'];
		$queue = [
			$identifier => [$singleRecord]
		];
		$this->subject->_set('queue', $queue);

		$this->subject->expects($this->once())
			->method('preProcessSingle')
			->with($singleRecord, $mockTask);

		$this->subject->process($importDemand);
	}

	/**
	 * @test
	 */
	public function processConverts() {
		$this->subject = $this->getAccessibleMock(
			ImportProcessor::class,
			['convertSingle']
		);

		$identifier = 'foo';
		$importDemand = $this->getMock(
			ImportDemand::class, ['getTasks']
		);
		$mockTask = $this->getMock(
			ImportTask::class, ['getIdentifier', 'getTarget']
		);
		$mockTarget = $this->getMock(
			DataTargetInterface::class
		);
		$importDemand->expects($this->any())
			->method('getTasks')
			->will($this->returnValue([$mockTask]));
		$mockTask->expects($this->any())
			->method('getIdentifier')
			->will($this->returnValue($identifier));
		$mockTask->expects($this->any())
			->method('getTarget')
			->will($this->returnValue($mockTarget));

		$mockPersistenceManager = $this->getAccessibleMock(
			PersistenceManager::class,
			['persistAll']
		);
		$this->subject->injectPersistenceManager($mockPersistenceManager);

		$singleRecord = ['foo' => 'bar'];
		$queue = [
			$identifier => [$singleRecord]
		];
		$this->subject->_set('queue', $queue);

		$this->subject->expects($this->once())
			->method('convertSingle')
			->with($singleRecord, $mockTask);

		$this->subject->process($importDemand);
	}

	/**
	 * @test
	 */
	public function processPostProcesses() {
		$this->subject = $this->getAccessibleMock(
			ImportProcessor::class,
			['postProcessSingle']
		);

		$identifier = 'foo';
		$importDemand = $this->getMock(
			ImportDemand::class, ['getTasks']
		);
		$mockTask = $this->getMock(
			ImportTask::class, ['getIdentifier', 'getTarget']
		);
		$mockTarget = $this->getMock(
			DataTargetInterface::class
		);
		$importDemand->expects($this->any())
			->method('getTasks')
			->will($this->returnValue([$mockTask]));
		$mockTask->expects($this->any())
			->method('getIdentifier')
			->will($this->returnValue($identifier));
		$mockTask->expects($this->any())
			->method('getTarget')
			->will($this->returnValue($mockTarget));

		$mockPersistenceManager = $this->getAccessibleMock(
			PersistenceManager::class,
			['persistAll']
		);
		$this->subject->injectPersistenceManager($mockPersistenceManager);

		$singleRecord = ['foo' => 'bar'];
		$queue = [
			$identifier => [$singleRecord]
		];
		$this->subject->_set('queue', $queue);

		$this->subject->expects($this->once())
			->method('postProcessSingle')
			->with($singleRecord, $singleRecord, $mockTask);

		$this->subject->process($importDemand);
	}

	/**
	 * @test
	 */
	public function processExecutesFinishers() {
		$identifier = 'foo';
		$queue = [
			$identifier => [
				['bar']
			]
		];
		$mockTask = $this->getMock(
			ImportTask::class,
			['getIdentifier', 'getTarget', 'getFinishers']
		);
		$mockFinisher = $this->getMockForAbstractClass(
			FinisherInterface::class
		);
		$finisherConfig = ['baz'];
		$mockDemand = $this->getMock(
				ImportDemand::class, ['getTasks']);
		$mockDemand->expects($this->once())
			->method('getTasks')
			->will($this->returnValue([$mockTask]));
		$mockTarget = $this->getMock(
				DataTargetInterface::class);
		$this->subject = $this->getAccessibleMock(
				ImportProcessor::class,
				['convertSingle']
		);
		$this->subject->_set('queue', $queue);
		$mockTask->expects($this->any())
			->method('getIdentifier')
			->will($this->returnValue($identifier));
		$mockTask->expects($this->once())
			->method('getTarget')
			->will($this->returnValue($mockTarget));
		$mockTask->expects($this->once())
			->method('getFinishers')
			->will($this->returnValue([$mockFinisher]));
		$mockFinisher->expects($this->once())
			->method('isDisabled')
			->will($this->returnValue(false));
		$mockFinisher->expects($this->once())
			->method('getConfiguration')
			->will($this->returnValue($finisherConfig));
		$mockFinisher->expects($this->once())
			->method('process');
		$mockPersistenceManager = $this->getMock(
				PersistenceManager::class, ['persistAll']
		);
		$this->subject->injectPersistenceManager(
				$mockPersistenceManager
		);

		$this->subject->process($mockDemand);
	}
}
