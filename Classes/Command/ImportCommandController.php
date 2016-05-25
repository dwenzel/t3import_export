<?php
namespace CPSIT\T3importExport\Command;

use CPSIT\T3importExport\Domain\Factory\ImportSetFactory;
use CPSIT\T3importExport\Domain\Factory\ImportTaskFactory;
use CPSIT\T3importExport\Domain\Model\Dto\ImportDemand;
use CPSIT\T3importExport\Service\ImportProcessor;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;
use TYPO3\CMS\Extbase\Service\TypoScriptService;

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
class ImportCommandController extends CommandController {

	/**
	 * @var array
	 */
	protected $settings;

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
	 * @var ConfigurationManager
	 */
	protected $configurationManager;

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
	 * @param ConfigurationManager $configurationManager
	 */
	public function injectConfigurationManager(ConfigurationManager $configurationManager) {
		$this->configurationManager = $configurationManager;

		$extbaseFrameworkConfiguration = $this->configurationManager->getConfiguration(
			ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK
		);
		if (isset($extbaseFrameworkConfiguration['settings']['importProcessor'])) {
			$this->settings = $extbaseFrameworkConfiguration['settings']['importProcessor'];
		}
	}

	/**
	 * Import task command
	 * Performs predefined import tasks
	 *
	 * @param string $identifier Task: Identifier of the task which should be performed
	 * @param bool $dryRun Dry run: If set nothing will be saved
	 */
	public function taskCommand($identifier, $dryRun = FALSE) {
		/** @var ImportDemand $importDemand */
		$importDemand = $this->objectManager->get(
			ImportDemand::class
		);

		if (isset($this->settings['tasks'][$identifier])) {
			$taskSettings = $this->settings['tasks'][$identifier];
			$task = $this->importTaskFactory->get(
				$taskSettings, $identifier
			);

			$importDemand->setTasks([$task]);

			$this->importProcessor->buildQueue($importDemand);
			if (!$dryRun) {
				$result = $this->importProcessor->process($importDemand);
			}
		}
	}

	/**
	 * Import set command
	 * Performs predefined import sets
	 *
	 * @param string $identifier Set: Identifier of the set which should be performed
	 * @param bool $dryRun Dry run: If set nothing will be saved
	 */
	public function setCommand($identifier, $dryRun = FALSE) {
		/** @var ImportDemand $importDemand */
		$importDemand = $this->objectManager->get(
			ImportDemand::class
		);

		if (isset($this->settings['sets'][$identifier])) {
			$set = $this->importSetFactory->get(
				$this->settings['sets'][$identifier], $identifier
			);
			$importDemand->setTasks($set->getTasks());
			$this->importProcessor->buildQueue($importDemand);
			if (!$dryRun) {
				$result = $this->importProcessor->process($importDemand);
			}
		}
	}
}
