<?php
namespace CPSIT\T3importExport\Command;

use CPSIT\T3importExport\Controller\ImportController;
use CPSIT\T3importExport\Domain\Factory\TransferSetFactory;
use CPSIT\T3importExport\Domain\Factory\TransferTaskFactory;
use CPSIT\T3importExport\Domain\Model\Dto\TaskDemand;
use CPSIT\T3importExport\Service\DataTransferProcessor;
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

/**
 * Class ImportCommandController
 * Provides import commands for cli and scheduler tasks
 *
 * @package CPSIT\T3importExport\Command
 */
class ImportCommandController extends CommandController
{

    /**
     * @var array
     */
    protected $settings;

    /**
     * @var DataTransferProcessor
     */
    protected $importProcessor;

    /**
     * @var \CPSIT\T3importExport\Domain\Factory\TransferTaskFactory
     */
    protected $transferTaskFactory;

    /**
     * @var \CPSIT\T3importExport\Domain\Factory\TransferSetFactory
     */
    protected $importSetFactory;

    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * Injects the import processor
     *
     * @param DataTransferProcessor $importProcessor
     */
    public function injectDataTransferProcessor(DataTransferProcessor $importProcessor)
    {
        $this->importProcessor = $importProcessor;
    }

    /**
     * Injects the import task factory
     *
     * @param TransferTaskFactory $importTaskFactory
     */
    public function injectTransferTaskFactory(TransferTaskFactory $importTaskFactory)
    {
        $this->transferTaskFactory = $importTaskFactory;
    }

    /**
     * Injects the import set factory
     *
     * @param TransferSetFactory $importSetFactory
     */
    public function injectImportSetFactory(TransferSetFactory $importSetFactory)
    {
        $this->importSetFactory = $importSetFactory;
    }

    /**
     * Injects the configuration manager and loads the TypoScript settings
     *
     * @param ConfigurationManager $configurationManager
     */
    public function injectConfigurationManager(ConfigurationManager $configurationManager)
    {
        $this->configurationManager = $configurationManager;

        $extbaseFrameworkConfiguration = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK
        );

        if (isset($extbaseFrameworkConfiguration['settings'][ImportController::SETTINGS_KEY])) {
            $this->settings = $extbaseFrameworkConfiguration['settings'][ImportController::SETTINGS_KEY];
        }
    }

    /**
     * Import task command
     * Performs predefined import tasks
     *
     * @param string $identifier Identifier of task which should be performed
     * @param bool $dryRun If set nothing will be saved
     */
    public function taskCommand($identifier, $dryRun = false)
    {
        /** @var TaskDemand $importDemand */
        $importDemand = $this->objectManager->get(
            TaskDemand::class
        );

        if (isset($this->settings['tasks'][$identifier])) {
            $taskSettings = $this->settings['tasks'][$identifier];
            $task = $this->transferTaskFactory->get(
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
     * @param string $identifier Identifier of set which should be performed
     * @param bool $dryRun If set nothing will be saved
     * @return void
     */
    public function setCommand($identifier, $dryRun = false)
    {
        /** @var TaskDemand $importDemand */
        $importDemand = $this->objectManager->get(
            TaskDemand::class
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
