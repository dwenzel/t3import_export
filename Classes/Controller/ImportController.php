<?php
namespace CPSIT\T3import\Controller;

use CPSIT\T3import\Domain\Factory\ImportSetFactory;
use CPSIT\T3import\Domain\Factory\ImportTaskFactory;
use CPSIT\T3import\Domain\Model\Dto\ImportDemand;
use CPSIT\T3import\Domain\Model\ImportSet;
use CPSIT\T3import\Service\ImportProcessor;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use CPSIT\T3import\Domain\Model\Dto\DemandInterface;
use CPSIT\T3import\Domain\Model\ImportTask;
use Webfox\T3events\Domain\Model\Task;

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
class ImportController extends ActionController {

	/**
	 * @var ImportProcessor
	 */
	protected $importProcessor;

	/**
	 * @var \CPSIT\T3import\Domain\Factory\ImportTaskFactory
	 */
	protected $importTaskFactory;

	/**
	 * @var \CPSIT\T3import\Domain\Factory\ImportSetFactory
	 */
	protected $importSetFactory;

	/**
	 * Injects the event import processor
	 *
	 * @param ImportProcessor $importProcessor
	 */
	public function injectImportProcessor(ImportProcessor $importProcessor) {
		$this->importProcessor = $importProcessor;
	}

	/**
	 * @param ImportTaskFactory $importTaskFactory
	 */
	public function injectImportTaskFactory(ImportTaskFactory $importTaskFactory) {
		$this->importTaskFactory = $importTaskFactory;
	}

	/**
	 * @param ImportSetFactory $importSetFactory
	 */
	public function injectImportSetFactory(ImportSetFactory $importSetFactory) {
		$this->importSetFactory = $importSetFactory;
	}

	/**
	 * Index action
	 */
	public function indexAction() {
		$this->view->assignMultiple(
			[
				'tasks' => $this->buildTasksFromSettings($this->settings['importProcessor']['tasks']),
				'sets' => $this->buildSetsFromSettings($this->settings['importProcessor']['sets']),
				'settings' => $this->settings['importProcessor']
			]
		);
	}

	/**
	 * Import
	 *
	 * @param string $task
	 */
	public function importTaskAction($task) {
		/** @var ImportDemand $importDemand */
		$importDemand = $this->objectManager->get(
			ImportDemand::class
		);
		$importDemand->setTasks([$task]);

		$this->importProcessor->buildQueue($importDemand);
		$result = $this->importProcessor->process();
		$this->view->assignMultiple(
			[
				'task' => $task,
				'result' => $result
			]
		);
	}

	/**
	 * Import
	 *
	 * @param string $set
	 */
	public function importSetAction($set) {
		/** @var ImportDemand $importDemand */
		$importDemand = $this->objectManager->get(
			ImportDemand::class
		);
		if (isset($this->settings['importProcessor']['sets'][$set])) {
			$set = $this->importSetFactory->get(
				$set,
				$this->settings['importProcessor']['sets'][$set]
			);
			$tasks = [];
			/** @var ImportTask $task */
			foreach ($task = $set->getTasks() as $task) {
				$tasks[] = $task->getIdentifier();
			}
			$importDemand->setTasks($tasks);
		}

		$this->importProcessor->buildQueue($importDemand);
		$this->importProcessor->process();
	}

	/**
	 * Gets tasks from settings
	 *
	 * @param $settings
	 * @return array
	 */
	protected function buildTasksFromSettings($settings) {
		$tasks = [];

		if (is_array($settings)) {
			foreach ($settings as $identifier => $taskSettings) {
				$tasks[$identifier] = $this->importTaskFactory->get(
					$identifier,
					$taskSettings
				);
			}
		}

		return $tasks;
	}

	/**
	 * Gets tasks from settings
	 *
	 * @param $settings
	 * @return array
	 */
	protected function buildSetsFromSettings($settings) {
		$sets = [];

		if (is_array($settings)) {
			foreach ($settings as $identifier => $setSettings) {
				$sets[$identifier] = $this->importSetFactory->get($identifier, $setSettings);
			}
		}

		return $sets;
	}

}