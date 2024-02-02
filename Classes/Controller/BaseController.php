<?php
namespace CPSIT\T3importExport\Controller;

use Psr\Http\Message\ResponseInterface;
use CPSIT\T3importExport\Domain\Factory\TransferSetFactory;
use CPSIT\T3importExport\Domain\Factory\TransferTaskFactory;
use CPSIT\T3importExport\Domain\Model\Dto\TaskDemand;
use CPSIT\T3importExport\Service\DataTransferProcessor;
use CPSIT\T3importExport\InvalidConfigurationException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

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
abstract class BaseController extends ActionController
{

    /**
     * @var DataTransferProcessor
     */
    protected $dataTransferProcessor;

    /**
     * @var TransferTaskFactory
     */
    protected $transferTaskFactory;

    /**
     * @var TransferSetFactory
     */
    protected $transferSetFactory;

    /**
     * Injects the event import processor
     *
     * @param DataTransferProcessor $dataTransferProcessor
     */
    public function injectDataTransferProcessor(DataTransferProcessor $dataTransferProcessor): void
    {
        $this->dataTransferProcessor = $dataTransferProcessor;
    }

    /**
     * @param TransferTaskFactory $importTaskFactory
     */
    public function injectTransferTaskFactory(TransferTaskFactory $importTaskFactory): void
    {
        $this->transferTaskFactory = $importTaskFactory;
    }

    /**
     * @param TransferSetFactory $importSetFactory
     */
    public function injectTransferSetFactory(TransferSetFactory $importSetFactory): void
    {
        $this->transferSetFactory = $importSetFactory;
    }

    /**
     * Gets the settings key
     *
     * @return string
     */
    abstract public function getSettingsKey();

    /**
     * Index action
     *
     * @throws InvalidConfigurationException
     */
    public function indexAction(): ResponseInterface
    {
        $settingsKey = $this->getSettingsKey();
        $tasks = [];
        $sets = [];
        if (isset($this->settings[$settingsKey]['tasks'])) {
            $tasks = $this->buildTasksFromSettings($this->settings[$settingsKey]['tasks']);
        }
        if (isset($this->settings[$settingsKey]['sets'])) {
            $sets = $this->buildSetsFromSettings($this->settings[$settingsKey]['sets']);
        }

        $this->view->assignMultiple(
            [
                'tasks' => $tasks,
                'sets' => $sets,
                'settings' => $this->settings[$settingsKey]
            ]
        );
        return $this->htmlResponse();
    }

    /**
     * Performs the task action
     *
     * @param string $identifier
     * @throws InvalidConfigurationException
     */
    protected function taskAction($identifier): void
    {
        /** @var TaskDemand $importDemand */
        $importDemand = GeneralUtility::makeInstance(
            TaskDemand::class
        );
        $task = $this->transferTaskFactory->get(
            $this->settings[$this->getSettingsKey()]['tasks'][$identifier], $identifier
        );
        $importDemand->setTasks([$task]);

        $this->dataTransferProcessor->buildQueue($importDemand);
        $result = $this->dataTransferProcessor->process($importDemand);
        $this->view->assignMultiple(
            [
                'task' => $identifier,
                'result' => $result
            ]
        );
    }

    /**
     * Performs the set action
     *
     * @param string $identifier
     * @throws InvalidConfigurationException
     */
    protected function setAction($identifier): void
    {
        $settingsKey = $this->getSettingsKey();

        /** @var TaskDemand $importDemand */
        $importDemand = $this->objectManager->get(
            TaskDemand::class
        );
        if (isset($this->settings[$settingsKey]['sets'][$identifier])) {
            $set = $this->transferSetFactory->get(
                $this->settings[$settingsKey]['sets'][$identifier], $identifier
            );
            $importDemand->setTasks($set->getTasks());
        }

        $this->dataTransferProcessor->buildQueue($importDemand);
        $result = $this->dataTransferProcessor->process($importDemand);
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
    protected function buildTasksFromSettings($settings): array
    {
        $tasks = [];

        if (is_array($settings)) {
            foreach ($settings as $identifier => $taskSettings) {
                $tasks[$identifier] = $this->transferTaskFactory->get(
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
    protected function buildSetsFromSettings($settings): array
    {
        $sets = [];

        if (is_array($settings)) {
            foreach ($settings as $identifier => $setSettings) {
                $sets[$identifier] = $this->transferSetFactory->get($setSettings, $identifier);
            }
        }

        return $sets;
    }
}
