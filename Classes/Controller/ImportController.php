<?php
namespace CPSIT\T3importExport\Controller;

use CPSIT\T3importExport\Domain\Factory\ImportSetFactory;
use CPSIT\T3importExport\Domain\Factory\ImportTaskFactory;
use CPSIT\T3importExport\Domain\Model\Dto\ImportDemand;
use CPSIT\T3importExport\Domain\Model\ImportSet;
use CPSIT\T3importExport\Service\ImportProcessor;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use CPSIT\T3importExport\Domain\Model\Dto\DemandInterface;
use CPSIT\T3importExport\Domain\Model\ImportTask;
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
	 * @var \CPSIT\T3importExport\Domain\Factory\ImportTaskFactory
	 */
	protected $importTaskFactory;

	/**
	 * @var \CPSIT\T3importExport\Domain\Factory\ImportSetFactory
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
	 * Import task action
	 *
	 * @param string $identifier
	 */
	public function importTaskAction($identifier) {
		/** @var ImportDemand $importDemand */
		$importDemand = $this->objectManager->get(
			ImportDemand::class
		);
		$task = $this->importTaskFactory->get(
			$this->settings['importProcessor']['tasks'][$identifier], $identifier
		);
		$importDemand->setTasks([$task]);

		$this->importProcessor->buildQueue($importDemand);
		$result = $this->importProcessor->process($importDemand);
		$this->view->assignMultiple(
			[
				'task' => $identifier,
				'result' => $result
			]
		);
	}

	/**
	 * Import
	 *
	 * @param string $identifier
	 */
	public function importSetAction($identifier) {
		/** @var ImportDemand $importDemand */
		$importDemand = $this->objectManager->get(
			ImportDemand::class
		);
		if (isset($this->settings['importProcessor']['sets'][$identifier])) {
			$set = $this->importSetFactory->get(
				$this->settings['importProcessor']['sets'][$identifier], $identifier
			);
			$importDemand->setTasks($set->getTasks());
		}

		$this->importProcessor->buildQueue($importDemand);
		$result = $this->importProcessor->process($importDemand);
		$this->view->assignMultiple(
			[
				'set' => $identifier,
				'result' => $result
			]
		);
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
					$taskSettings, $identifier
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
				$sets[$identifier] = $this->importSetFactory->get($setSettings, $identifier);
			}
		}

		return $sets;
	}

}
