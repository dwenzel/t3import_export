<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Controller;

use CPSIT\ImportExportCore\Configuration\ConfigurationHandlerInterface;
use CPSIT\ImportExportCore\Exception\InvalidConfigurationException;
use CPSIT\ImportExportCore\Exception\MissingClassException;
use CPSIT\ImportExportCore\Exception\MissingInterfaceException;
use CPSIT\ImportExportCore\Factory\TransferTaskFactory;
use CPSIT\T3importExport\Domain\Factory\TransferSetFactory;
use CPSIT\T3importExport\Service\TransferService;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
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
     * Note: this constant must be overridden in child classes
     * with the correct path to the templates for the controller.
     */
    public const string TEMPLATE_PATH_INDEX = 'Fake/Index';

    protected TransferService $transferService;

    /**
     * @var TransferTaskFactory
     */
    protected TransferTaskFactory $transferTaskFactory;

    /**
     * @var TransferSetFactory
     */
    protected TransferSetFactory $transferSetFactory;
    protected ModuleTemplate $moduleTemplate;
    protected ?ConfigurationHandlerInterface $configurationHandler = null;

    /**
     * Tasks and sets of the module from the YAML configuration
     *
     * @var array<string, mixed>
     */
    protected array $configuration = [];

    public function __construct(
        protected ModuleTemplateFactory $moduleTemplateFactory,
        protected PageRenderer $pageRenderer
    ) {}

    public function initializeAction(): void
    {
        $this->moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $this->moduleTemplate->setFlashMessageQueue($this->getFlashMessageQueue('module.t3import_export'));
        $this->moduleTemplate->getDocHeaderComponent()->disable();
        $this->configuration = $this->getConfiguration();
    }

    public function injectConfigurationHandler(ConfigurationHandlerInterface $configurationHandler): void
    {
        $this->configurationHandler = $configurationHandler;
    }

    /**
     * Returns the tasks and sets of the module (import or export)
     * from the YAML configuration.
     *
     * @return array<string, mixed>
     */
    protected function getConfiguration(): array
    {
        if (!$this->configurationHandler instanceof ConfigurationHandlerInterface) {
            return [];
        }
        $configuration = $this->configurationHandler->getFullConfiguration()[$this->getSettingsKey()] ?? [];

        return is_array($configuration) ? $configuration : [];
    }

    public function injectTransferService(TransferService $transferService): void
    {
        $this->transferService = $transferService;
    }

    public function injectTransferTaskFactory(TransferTaskFactory $importTaskFactory): void
    {
        $this->transferTaskFactory = $importTaskFactory;
    }

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
        $tasks = [];
        $sets = [];
        if (isset($this->configuration['tasks'])) {
            $tasks = $this->buildTasksFromSettings($this->configuration['tasks']);
        }
        if (isset($this->configuration['sets'])) {
            $sets = $this->buildSetsFromSettings($this->configuration['sets']);
        }

        $this->moduleTemplate->assignMultiple(
            [
                'tasks' => $tasks,
                'sets' => $sets,
            ]
        );
        return $this->moduleTemplate->renderResponse(static::TEMPLATE_PATH_INDEX);
    }

    /**
     * Performs the task action
     *
     * @param string $identifier
     * @throws InvalidConfigurationException
     * @throws MissingClassException
     * @throws MissingInterfaceException|\CPSIT\ImportExportCore\Exception\MissingInterfaceException
     */
    protected function taskAction($identifier): void
    {
        $task = $this->transferTaskFactory->create(
            (string)$identifier,
            $this->configuration['tasks'][$identifier]
        );
        $result = $this->transferService->run([$task]);
        $this->moduleTemplate->assignMultiple(
            [
                'task' => $identifier,
                'result' => $result,
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
        $tasks = [];
        if (isset($this->configuration['sets'][$identifier])) {
            $set = $this->transferSetFactory->get(
                $this->configuration['sets'][$identifier],
                (string)$identifier
            );
            $tasks = $set->getTasks();
        }

        $result = $this->transferService->run($tasks);
        $this->moduleTemplate->assignMultiple(
            [
                'set' => $identifier,
                'result' => $result,
            ]
        );
    }

    /**
     * Gets tasks from settings
     */
    protected function buildTasksFromSettings($settings): array
    {
        $tasks = [];

        if (is_array($settings)) {
            foreach ($settings as $identifier => $taskSettings) {
                $tasks[$identifier] = $this->transferTaskFactory->create(
                    (string)$identifier,
                    $taskSettings
                );
            }
        }

        return $tasks;
    }

    /**
     * Gets tasks from settings
     */
    protected function buildSetsFromSettings($settings): array
    {
        $sets = [];

        if (is_array($settings)) {
            foreach ($settings as $identifier => $setSettings) {
                $sets[$identifier] = $this->transferSetFactory->get($setSettings, (string)$identifier);
            }
        }

        return $sets;
    }
}
