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
use CPSIT\T3importExport\Component\PostProcessor\AbstractPostProcessor;
use CPSIT\T3importExport\Component\PreProcessor\AbstractPreProcessor;
use CPSIT\T3importExport\ConfigurableInterface;
use CPSIT\T3importExport\Domain\Model\Dto\DemandInterface;
use CPSIT\T3importExport\Domain\Model\TaskResult;
use CPSIT\T3importExport\Domain\Model\TransferTask;
use CPSIT\T3importExport\LoggingInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

/**
 * Class DataTransferProcessor
 *
 * @package CPSIT\T3importExport\Service
 */
class DataTransferProcessor
{
    /**
     * Queue
     * Records to import
     */
    protected array $queue = [];

    /**
     * @var PersistenceManager
     */
    protected $persistenceManager;

    /**
     * injects the persistence manager
     *
     * @param PersistenceManager $persistenceManager
     */
    public function injectPersistenceManager(PersistenceManager $persistenceManager)
    {
        $this->persistenceManager = $persistenceManager;
    }

    public function withQueue(array $queue): self
    {
        $this->queue = $queue;
        return $this;
    }

    /**
     * gets the queue
     *
     * @return array
     */
    public function getQueue(): array
    {
        return $this->queue;
    }

    /**
     * builds the import queue
     *
     * @param DemandInterface
     */
    public function buildQueue(DemandInterface $demand)
    {
        $tasks = $demand->getTasks();
        foreach ($tasks as $task) {
            /** @var TransferTask $task */
            $dataSource = $task->getSource();
            $configuration = [];
            if ($dataSource instanceof ConfigurableInterface) {
                $configuration = $dataSource->getConfiguration();
            }
            $recordsToImport = $dataSource->getRecords(
                $configuration
            );

            $this->queue[$task->getIdentifier()] = $recordsToImport;
        }
    }

    /**
     * Processes the queue
     *
     * @param DemandInterface
     * @return array
     */
    public function process(DemandInterface $demand)
    {
        /** @var TaskResult $result */
        $result = GeneralUtility::makeInstance(TaskResult::class);
        $tasks = $demand->getTasks();
        foreach ($tasks as $task) {
            /** @var TransferTask $task */
            if (!isset($this->queue[$task->getIdentifier()])) {
                continue;
            }
            $records = $this->queue[$task->getIdentifier()];

            $recordsArray = [];
            foreach ($records as $record) {
                if (is_object($record) && method_exists($record, '_getCleanProperties')) {
                    $recordsArray[] = $record->_getCleanProperties();
                }
            }

            $this->processInitializers($records, $task, $result);

            if ($recordsArray) {
                $target = $task->getTarget();
                $targetConfig = null;
                if ($target instanceof ConfigurableInterface) {
                    $targetConfig = $target->getConfiguration();
                }

                foreach ($recordsArray as &$record) {
                    $this->preProcessSingle($record, $task, $result);
                    $convertedRecord = $this->convertSingle($record, $task, $result);
                    $this->postProcessSingle($convertedRecord, $record, $task, $result);
                    $target->persist($convertedRecord, $targetConfig);
                    $result->add($convertedRecord);
                }

                $target->persistAll($result, $targetConfig);
            }

            $this->processFinishers($recordsArray, $task, $result);
        }

        return $result->toArray();
    }

    /**
     * Processes all initializers
     *
     * @param array $records Processed records
     * @param TransferTask $task Import task
     * @param TaskResult $result
     */
    protected function processInitializers(&$records, TransferTask $task, TaskResult $result)
    {
        $initializers = $task->getInitializers();
        foreach ($initializers as $initializer) {
            /** @var InitializerInterface $initializer */
            $config = $initializer->getConfiguration();
            if (!$initializer->isDisabled($config, [], $result)) {
                $initializer->process($config, $records);
            }
            $this->gatherMessages($initializer, $result);
        }
    }

    /**
     * Gathers messages from component and
     * adds them to the message queue of the result
     * @param object $component
     * @param array|\Iterator|TaskResult $result
     */
    protected function gatherMessages($component, TaskResult $result)
    {
        if ($component instanceof LoggingInterface
            && $result instanceof TaskResult
        ) {
            $result->addMessages(
                $component->getAndPurgeMessages()
            );
        }
    }

    /**
     * Pre processes a single record if any preprocessor is configured
     *
     * @param array $record
     * @param TransferTask $task
     * @param TaskResult $result
     */
    protected function preProcessSingle(&$record, TransferTask $task, TaskResult $result)
    {
        $preProcessors = $task->getPreProcessors();
        foreach ($preProcessors as $preProcessor) {
            /** @var AbstractPreProcessor $preProcessor */
            $singleConfig = $preProcessor->getConfiguration();
            if (!$preProcessor->isDisabled($singleConfig, $record, $result)) {
                $preProcessor->process($singleConfig, $record);
            }
            $this->gatherMessages($preProcessor, $result);
        }
    }

    /**
     * Converts a record into an object
     *
     * @param array $record Record which should be converted
     * @param TransferTask $task Import type
     * @param TaskResult $result
     * @return mixed The converted object
     */
    protected function convertSingle(array $record, TransferTask $task, TaskResult $result)
    {
        $convertedRecord = $record;
        $converters = $task->getConverters();
        foreach ($converters as $converter) {
            /** @var AbstractConverter $converter */
            $config = $converter->getConfiguration();
            if (!$converter->isDisabled($config, $record, $result)) {
                $convertedRecord = $converter->convert($convertedRecord, $config);
            }
            $this->gatherMessages($converter, $result);
        }

        return $convertedRecord;
    }

    /**
     * Post processes a single record if any post processor is configured
     *
     * @param mixed $convertedRecord
     * @param array $record
     * @param TransferTask $task
     * @param TaskResult $result
     */
    protected function postProcessSingle(&$convertedRecord, &$record, TransferTask $task, TaskResult $result)
    {
        $postProcessors = $task->getPostProcessors();
        foreach ($postProcessors as $postProcessor) {
            /** @var AbstractPostProcessor $postProcessor */
            $config = $postProcessor->getConfiguration();
            if (!$postProcessor->isDisabled($config, $record, $result)) {
                $postProcessor->process(
                    $config,
                    $convertedRecord,
                    $record
                );
            }
            $this->gatherMessages($postProcessor, $result);
        }
    }

    /**
     * Processes all finishers
     *
     * @param array $records Processed records
     * @param TransferTask $task Import task
     * @param array|\Iterator|null $result
     */
    protected function processFinishers(&$records, TransferTask $task, &$result)
    {
        $finishers = $task->getFinishers();
        foreach ($finishers as $finisher) {
            /** @var FinisherInterface $finisher */
            $config = $finisher->getConfiguration();
            if (!$finisher->isDisabled($config, [], $result)) {
                $finisher->process($config, $records, $result);
            }
            $this->gatherMessages($finisher, $result);
        }
    }
}
