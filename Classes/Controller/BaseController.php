<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Controller;

use CPSIT\ImportExportCore\Configuration\ConfigurationHandlerInterface;
use CPSIT\ImportExportCore\Exception\InvalidConfigurationException;
use CPSIT\ImportExportCore\Exception\MissingClassException;
use CPSIT\ImportExportCore\Exception\MissingInterfaceException;
use CPSIT\T3importExport\Domain\Factory\TransferSetFactory;
use CPSIT\T3importExport\Domain\Factory\TransferTaskFactory;
use CPSIT\T3importExport\Domain\Model\Dto\TaskDemand;
use CPSIT\T3importExport\Service\DataTransferProcessor;
use CPSIT\T3importExport\Utility\DeprecationUtility;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
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
     * Note: this constant must be overridden in child classes
     * with the correct path to the templates for the controller.
     */
    public const string TEMPLATE_PATH_INDEX = 'Fake/Index';

    /**
     * @var DataTransferProcessor
     */
    protected DataTransferProcessor $dataTransferProcessor;

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
     * Identifiers of tasks and sets defined in TypoScript
     *
     * @var string[]
     */
    protected array $typoScriptIdentifiers = [];
    public function __construct(
        protected ModuleTemplateFactory $moduleTemplateFactory,
        protected PageRenderer $pageRenderer
    ) {}

    public function initializeAction(): void
    {
        $this->moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $this->moduleTemplate->setFlashMessageQueue($this->getFlashMessageQueue('module.t3import_export'));
        $this->moduleTemplate->getDocHeaderComponent()->disable();
        $this->typoScriptIdentifiers = $this->getTypoScriptIdentifiers($this->settings);
        if ($this->typoScriptIdentifiers !== []) {
            DeprecationUtility::trigger(
                'Tasks and sets in TypoScript (module.tx_t3importexport.settings) are deprecated since 3.1 and will be removed in 4.0. Move them to YAML files.'
            );
        }
        $this->settings = $this->mergeYamlConfiguration($this->settings);
    }

    /**
     * @param array<string, mixed> $settings
     * @return string[]
     */
    protected function getTypoScriptIdentifiers(array $settings): array
    {
        $identifiers = [];
        foreach (['tasks', 'sets'] as $type) {
            $definitions = $settings[$this->getSettingsKey()][$type] ?? null;
            if (is_array($definitions)) {
                $identifiers = [...$identifiers, ...array_map('strval', array_keys($definitions))];
            }
        }

        return $identifiers;
    }

    public function injectConfigurationHandler(ConfigurationHandlerInterface $configurationHandler): void
    {
        $this->configurationHandler = $configurationHandler;
    }

    /**
     * Adds tasks and sets from the YAML configuration to the TypoScript
     * settings of the module. TypoScript takes precedence for identifiers
     * defined in both.
     *
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    protected function mergeYamlConfiguration(array $settings): array
    {
        if (!$this->configurationHandler instanceof ConfigurationHandlerInterface) {
            return $settings;
        }
        $settingsKey = $this->getSettingsKey();
        $yamlConfiguration = $this->configurationHandler->getFullConfiguration()[$settingsKey] ?? [];
        if (!is_array($yamlConfiguration)) {
            return $settings;
        }
        foreach (['tasks', 'sets'] as $type) {
            $fromYaml = is_array($yamlConfiguration[$type] ?? null) ? $yamlConfiguration[$type] : [];
            $fromTypoScript = is_array($settings[$settingsKey][$type] ?? null) ? $settings[$settingsKey][$type] : [];
            if ($fromYaml === [] && $fromTypoScript === []) {
                continue;
            }
            $settings[$settingsKey][$type] = array_replace($fromYaml, $fromTypoScript);
        }

        return $settings;
    }

    /**
     * Injects the event import processor
     */
    public function injectDataTransferProcessor(DataTransferProcessor $dataTransferProcessor): void
    {
        $this->dataTransferProcessor = $dataTransferProcessor;
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
        if ($this->typoScriptIdentifiers !== []) {
            $this->getFlashMessageQueue('module.t3import_export')->enqueue(
                GeneralUtility::makeInstance(
                    FlashMessage::class,
                    sprintf(
                        'Defined in TypoScript: %s. TypoScript configuration is deprecated and will be removed in version 4.0. Please move these tasks and sets to YAML files.',
                        implode(', ', $this->typoScriptIdentifiers)
                    ),
                    'Deprecated configuration',
                    ContextualFeedbackSeverity::WARNING,
                    false
                )
            );
        }
        $settingsKey = $this->getSettingsKey();
        $tasks = [];
        $sets = [];
        if (isset($this->settings[$settingsKey]['tasks'])) {
            $tasks = $this->buildTasksFromSettings($this->settings[$settingsKey]['tasks']);
        }
        if (isset($this->settings[$settingsKey]['sets'])) {
            $sets = $this->buildSetsFromSettings($this->settings[$settingsKey]['sets']);
        }

        $this->moduleTemplate->assignMultiple(
            [
                'tasks' => $tasks,
                'sets' => $sets,
                'settings' => $this->settings[$settingsKey],
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
        /** @var TaskDemand $importDemand */
        $importDemand = GeneralUtility::makeInstance(
            TaskDemand::class
        );
        $task = $this->transferTaskFactory->get(
            $this->settings[$this->getSettingsKey()]['tasks'][$identifier],
            $identifier
        );
        $importDemand->setTasks([$task]);

        $this->dataTransferProcessor->buildQueue($importDemand);
        $result = $this->dataTransferProcessor->process($importDemand);
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
        $settingsKey = $this->getSettingsKey();

        /** @var TaskDemand $importDemand */
        $importDemand = GeneralUtility::makeInstance(
            TaskDemand::class
        );
        if (isset($this->settings[$settingsKey]['sets'][$identifier])) {
            $set = $this->getTransferSetFactory()->get(
                $this->settings[$settingsKey]['sets'][$identifier],
                $identifier
            );
            $importDemand->setTasks($set->getTasks());
        }

        $this->dataTransferProcessor->buildQueue($importDemand);
        $result = $this->dataTransferProcessor->process($importDemand);
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
                $tasks[$identifier] = $this->transferTaskFactory->get(
                    $taskSettings,
                    $identifier
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
                $sets[$identifier] = $this->getTransferSetFactory()->get($setSettings, $identifier);
            }
        }

        return $sets;
    }

    /**
     * Returns the set factory with the TypoScript settings of the module, so that
     * sets find tasks defined in TypoScript (they take precedence over YAML).
     */
    protected function getTransferSetFactory(): TransferSetFactory
    {
        $this->transferSetFactory->withSettings($this->settings);

        return $this->transferSetFactory;
    }
}
