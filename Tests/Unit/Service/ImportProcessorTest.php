<?php
namespace CPSIT\T3importExport\Tests\Service;

use CPSIT\T3importExport\Component\Finisher\FinisherInterface;
use CPSIT\T3importExport\Component\Initializer\InitializerInterface;
use CPSIT\T3importExport\Domain\Model\Dto\TaskDemand;
use CPSIT\T3importExport\Domain\Model\TransferTask;
use CPSIT\T3importExport\Domain\Model\TaskResult;
use CPSIT\T3importExport\Persistence\DataSourceInterface;
use CPSIT\T3importExport\Persistence\DataTargetInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use CPSIT\T3importExport\Service\DataTransferProcessor;
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
 * @package CPSIT\T3importExport\Tests\Service
 * @coversDefaultClass \CPSIT\T3importExport\Service\DataTransferProcessor
 */
class ImportProcessorTest extends UnitTestCase {

	/**
	 * @var \CPSIT\T3importExport\Service\DataTransferProcessor
	 */
	protected $subject;

	protected $taskResult;

	public function setUp() {
		$this->subject = $this->getAccessibleMock(
			DataTransferProcessor::class,
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
	 * @return \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
	 */
	public function createObjectManagerMock()
	{
		$this->taskResult = new TaskResult();

		$objectManagerMock = $this->getAccessibleMock(
			ObjectManager::class,
			['get']
		);
		$objectManagerMock->expects($this->any())
			->method('get')
			->with(TaskResult::class)
			->will($this->returnValue($this->taskResult));

		$this->subject->injectObjectManager($objectManagerMock);

		return $objectManagerMock;
	}

	/**
	 * @test
	 * @covers ::injectObjectManager
	 */
	public function injectObjectManagerForObjectSetsObjectManager()
	{
		$objectManagerMock = $this->createObjectManagerMock();

		$this->assertSame(
			$objectManagerMock,
			$this->subject->_get('objectManager')
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
			TransferTask::class,
			['getSource', 'getIdentifier']
		);
		$mockDemand = $this->getMock(
			TaskDemand::class, ['getTasks']
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
	public function processInitiallyReturnsEmptyIterator() {
		$importDemand = $this->getMock(
			TaskDemand::class, ['getTasks']
		);
		$importDemand->expects($this->any())
			->method('getTasks')
			->will($this->returnValue([]));

		$this->createObjectManagerMock();

		$this->assertSame(
			$this->taskResult->toArray(),
			$this->subject->process($importDemand)
		);
	}

	/**
	 * @test
	 */
	public function processDoesNotRunEmptyQueue() {
		$identifier = 'foo';
		$importDemand = $this->getMock(
			TaskDemand::class, ['getTasks']
		);
		$mockTask = $this->getMock(
			TransferTask::class, ['getIdentifier']
		);

		$importDemand->expects($this->any())
			->method('getTasks')
			->will($this->returnValue($mockTask));
		$mockTask->expects($this->any())
			->method('getIdentifier')
			->will($this->returnValue($identifier));

		$this->createObjectManagerMock();

		$this->assertSame(
			$this->taskResult->toArray(),
			$this->subject->process($importDemand)
		);
	}

	/**
	 * @test
	 */
	public function processPreProcesses() {
		$this->subject = $this->getAccessibleMock(
			DataTransferProcessor::class,
			['preProcessSingle']
		);

		$identifier = 'foo';
		$importDemand = $this->getMock(
			TaskDemand::class, ['getTasks']
		);
		$mockTask = $this->getMock(
			TransferTask::class, ['getIdentifier', 'getTarget']
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

		$this->createObjectManagerMock();

		$this->subject->process($importDemand);
	}

	/**
	 * @test
	 */
	public function processConverts() {
		$this->subject = $this->getAccessibleMock(
			DataTransferProcessor::class,
			['convertSingle']
		);

		$identifier = 'foo';
		$importDemand = $this->getMock(
			TaskDemand::class, ['getTasks']
		);
		$mockTask = $this->getMock(
			TransferTask::class, ['getIdentifier', 'getTarget']
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

		$this->createObjectManagerMock();

		$this->subject->process($importDemand);
	}

	/**
	 * @test
	 */
	public function processPostProcesses() {
		$this->subject = $this->getAccessibleMock(
			DataTransferProcessor::class,
			['postProcessSingle']
		);

		$identifier = 'foo';
		$importDemand = $this->getMock(
			TaskDemand::class, ['getTasks']
		);
		$mockTask = $this->getMock(
			TransferTask::class, ['getIdentifier', 'getTarget']
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

		$this->createObjectManagerMock();

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
			TransferTask::class,
			['getIdentifier', 'getTarget', 'getFinishers']
		);
		$mockFinisher = $this->getMockForAbstractClass(
			FinisherInterface::class
		);
		$finisherConfig = ['baz'];
		$mockDemand = $this->getMock(
				TaskDemand::class, ['getTasks']);
		$mockDemand->expects($this->once())
			->method('getTasks')
			->will($this->returnValue([$mockTask]));
		$mockTarget = $this->getMock(
				DataTargetInterface::class);
		$this->subject = $this->getAccessibleMock(
			DataTransferProcessor::class,
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
			->method('process')
            ->will($this->returnArgument(2));
		$mockPersistenceManager = $this->getMock(
				PersistenceManager::class, ['persistAll']
		);
		$this->subject->injectPersistenceManager(
				$mockPersistenceManager
		);

		$this->createObjectManagerMock();

		$this->subject->process($mockDemand);
	}

	/**
	 * @test
	 */
	public function processExecutesInitializers() {
		$identifier = 'foo';
		$queue = [
			$identifier => [
				['bar']
			]
		];
		$mockTask = $this->getMock(
			TransferTask::class,
			['getIdentifier', 'getTarget', 'getInitializers']
		);
		$mockInitializer = $this->getMockForAbstractClass(
			InitializerInterface::class
		);
		$initializerConfig = ['baz'];
		$mockDemand = $this->getMock(
			TaskDemand::class, ['getTasks']);
		$mockDemand->expects($this->once())
			->method('getTasks')
			->will($this->returnValue([$mockTask]));
		$mockTarget = $this->getMock(
			DataTargetInterface::class);
		$this->subject = $this->getAccessibleMock(
			DataTransferProcessor::class,
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
			->method('getInitializers')
			->will($this->returnValue([$mockInitializer]));
		$mockInitializer->expects($this->once())
			->method('isDisabled')
			->will($this->returnValue(false));
		$mockInitializer->expects($this->once())
			->method('getConfiguration')
			->will($this->returnValue($initializerConfig));
		$mockInitializer->expects($this->once())
			->method('process');
		$mockPersistenceManager = $this->getMock(
			PersistenceManager::class, ['persistAll']
		);
		$this->subject->injectPersistenceManager(
			$mockPersistenceManager
		);

		$this->createObjectManagerMock();

		$this->subject->process($mockDemand);
	}
}
