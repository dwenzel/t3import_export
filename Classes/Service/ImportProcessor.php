<?php
namespace CPSIT\T3importExport\Service;

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
use CPSIT\T3importExport\Component\Converter\AbstractConverter;
use CPSIT\T3importExport\Component\Finisher\FinisherInterface;
use CPSIT\T3importExport\Component\Initializer\InitializerInterface;
use CPSIT\T3importExport\ConfigurableInterface;
use CPSIT\T3importExport\Domain\Model\Dto\DemandInterface;
use CPSIT\T3importExport\Component\PostProcessor\AbstractPostProcessor;
use CPSIT\T3importExport\Component\PreProcessor\AbstractPreProcessor;
use CPSIT\T3importExport\Domain\Model\ImportTask;
use CPSIT\T3importExport\Domain\Model\Queue;
use CPSIT\T3importExport\Domain\Repository\QueueItemRepository;
use CPSIT\T3importExport\Domain\Repository\QueueRepository;
use CPSIT\T3importExport\Persistence\DataSourceQueueInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

/**
 * Class ImportProcessor
 *
 * @package CPSIT\T3importExport\Service
 */
class ImportProcessor
{
	/**
	 * Queue
	 * Records to import
	 *
	 * @var array
	 */
	protected $queue = [];

	/**
	 * @var \CPSIT\T3importExport\Domain\Repository\QueueRepository
	 */
	protected $persistenceManager;

	/**
	 * @var QueueRepository
	 */
	protected $queueRepository;

	/**
	 * @var QueueItemRepository
	 */
	protected $queueItemRepository;

	/**
	 * injects the persistence manager
	 *
	 * @param PersistenceManager $persistenceManager
	 */
	public function injectPersistenceManager(PersistenceManager $persistenceManager)
	{
		$this->persistenceManager = $persistenceManager;
	}

	/**
	 * injects the queue Repository manager
	 *
	 * @param QueueRepository $queueRepository
	 */
	public function injectQueueRepository(QueueRepository $queueRepository)
	{
		$this->queueRepository = $queueRepository;
	}

	/**
	 * injects the queue Repository manager
	 *
	 * @param QueueItemRepository $queueItemRepository
	 */
	public function injectQueueItemRepository(QueueItemRepository $queueItemRepository)
	{
		$this->queueItemRepository = $queueItemRepository;
	}

	/**
	 * gets the queue
	 *
	 * @return array
	 */
	public function getQueue()
	{
		return $this->queue;
	}

	/**
	 * builds the import queue
	 *
	 * @param \CPSIT\T3importExport\Domain\Model\Dto\DemandInterface
	 */
	public function buildQueue(DemandInterface $importDemand)
	{
		$tasks = $importDemand->getTasks();
		/** @var ImportTask $task */
		foreach ($tasks as $task) {
			// lookup for existing queue
			$queue = $this->queueRepository->getQueueForTask($task, 2);
			if ($queue == null)
			{
				// create Queue with QueueItems for Task
				$queue = $this->createQueueForTask($task, 2);
			}
			// add task-queue to process queue pool
			$this->queue[$task->getIdentifier()] = $queue;
		}
	}

	/**
	 * @param ImportTask $task
	 * @param $pid
	 * @return Queue
	 */
	private function createQueueForTask(ImportTask $task, $pid)
	{
		// TODO: this method maybe outsource to another file?
		// create Queue in pid 2
		$queue = $this->queueRepository->createWithTask($task, $pid);
		$this->queueRepository->add($queue);

		// create QueueItems
		$dataSource = $task->getSource();

		if ($dataSource instanceof DataSourceQueueInterface) {
			// iterate through the dataSource chunk-based (batch size of queue)
			$endOfSource = false;
			$offset = 0;

			while ($endOfSource != true) {
				// load indexes from Datasource chunk by chunk
				// endOfSource will be true (reference) if the sourceAdapter says "there is no more"
				$recordsIndexesToQueue = $dataSource->getRecordsIndexes(
					$dataSource->getConfiguration(),
					$queue->getBatchSize(),
					$offset,
					$endOfSource
				);
				// increase the current offset pointer for the next iteration step
				$offset += count($recordsIndexesToQueue);

				foreach ($recordsIndexesToQueue as $recordIndex)
				{
					$queueItem = $this->queueItemRepository->createWithIndex($recordIndex, $pid);
					$queue->addQueueItem($queueItem);
				}
				$this->queueRepository->persistTransaction($queue, true);
			}
			// flush
			$this->queueRepository->flushTransaction();
		} else {
			// do something here ... maybe throw
		}
		
		return $queue;
	}

	/**
	 * Processes the queue
	 *
	 * @param \CPSIT\T3importExport\Domain\Model\Dto\DemandInterface
	 * @return array
	 */
	public function process(DemandInterface $importDemand)
	{
		/*$result = [];
		$tasks = $importDemand->getTasks();
		foreach ($tasks as $task) {
			/** @var ImportTask $task */
			/*if (!isset($this->queue[$task->getIdentifier()])) {
				continue;
			}
			//$records = $this->queue[$task->getIdentifier()];
            $queueItems = $this->queue->getItems($task->getIdentifier());
			$this->processInitializers($records, $task);

			if ((bool) $records) {
				$target = $task->getTarget();
				$targetConfig = null;
				if ($target instanceof ConfigurableInterface) {
					$targetConfig = $target->getConfiguration();
				}

				foreach ($queueItems as $item) {
                    $record = $item->getRecord();
					$this->preProcessSingle($record, $task);
					$convertedRecord = $this->convertSingle($record, $task);
					$this->postProcessSingle($convertedRecord, $record, $task);
					$target->persist($convertedRecord, $targetConfig);
                    $this->queue->remove($item);
					$result[] = $convertedRecord;
				}
                $queue->flush();
                if (!$this->queue->has($task->getIdentifier())) {
                    $targetConfig['queueEmpty'] = true;
                    $this->queue->clear($task->getIdentifier());
                }
                $target->persistAll($result, $targetConfig);

            }

			$this->processFinishers($records, $task, $result);
		}

		return $result;*/
	}

	/**
	 * Pre processes a single record if any preprocessor is configured
	 *
	 * @param array $record
	 * @param ImportTask $task
	 */
	protected function preProcessSingle(&$record, ImportTask $task)
	{
		$preProcessors = $task->getPreProcessors();
		foreach ($preProcessors as $preProcessor) {
			/** @var AbstractPreProcessor $preProcessor */
			$singleConfig = $preProcessor->getConfiguration();
			if (!$preProcessor->isDisabled($singleConfig, $record)) {
				$preProcessor->process($singleConfig, $record);
			}
		}
	}

	/**
	 * Post processes a single record if any post processor is configured
	 *
	 * @param mixed $convertedRecord
	 * @param array $record
	 * @param ImportTask $task
	 */
	protected function postProcessSingle(&$convertedRecord, &$record, $task)
	{
		$postProcessors = $task->getPostProcessors();
		foreach ($postProcessors as $singleProcessor) {
			/** @var AbstractPostProcessor $singleProcessor */
			$config = $singleProcessor->getConfiguration();
			if (!$singleProcessor->isDisabled($config, $record)) {
				$singleProcessor->process(
					$config,
					$convertedRecord,
					$record
				);
			}
		}
	}

	/**
	 * Converts a record into an object
	 *
	 * @param array $record Record which should be converted
	 * @param ImportTask $task Import type
	 * @return mixed The converted object
	 */
	protected function convertSingle($record, $task)
	{
		$convertedRecord = $record;
		$converters = $task->getConverters();
		foreach ($converters as $converter) {
			/** @var AbstractConverter $converter */
			$config = $converter->getConfiguration();
			if (!$converter->isDisabled($config)) {
				$convertedRecord = $converter->convert($convertedRecord, $config);
			}
		}

		return $convertedRecord;
	}

	/**
	 * Processes all finishers
	 *
	 * @param array $records Processed records
	 * @param ImportTask $task Import task
	 * @param array $result
	 */
	protected function processFinishers(&$records, $task, &$result)
	{
		$finishers = $task->getFinishers();
		foreach ($finishers as $finisher) {
			/** @var FinisherInterface $finisher */
			$config = $finisher->getConfiguration();
			if (!$finisher->isDisabled($config)) {
				$finisher->process($config, $records, $result);
			}
		}
	}

	/**
	 * Processes all initializers
	 *
	 * @param array $records Processed records
	 * @param ImportTask $task Import task
	 */
	protected function processInitializers(&$records, $task)
	{
		$initializers = $task->getInitializers();
		foreach ($initializers as $initializer) {
			/** @var InitializerInterface $initializer */
			$config = $initializer->getConfiguration();
			if (!$initializer->isDisabled($config)) {
				$initializer->process($config, $records);
			}
		}
	}
}
