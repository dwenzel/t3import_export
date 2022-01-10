<?php

namespace CPSIT\T3importExport\Command;

use CPSIT\T3importExport\Command\Argument\SetArgument;
use CPSIT\T3importExport\Command\Argument\TaskArgument;
use CPSIT\T3importExport\Controller\ImportController;
use CPSIT\T3importExport\Domain\Factory\TransferSetFactory;
use CPSIT\T3importExport\Domain\Factory\TransferTaskFactory;
use CPSIT\T3importExport\Domain\Model\Dto\TaskDemand;
use CPSIT\T3importExport\InvalidConfigurationException;
use CPSIT\T3importExport\MissingClassException;
use CPSIT\T3importExport\MissingInterfaceException;
use CPSIT\T3importExport\Service\DataTransferProcessor;
use DWenzel\T3extensionTools\Command\ArgumentAwareInterface;
use DWenzel\T3extensionTools\Command\OptionAwareInterface;
use DWenzel\T3extensionTools\Traits\Command\ArgumentAwareTrait;
use DWenzel\T3extensionTools\Traits\Command\ConfigureTrait;
use DWenzel\T3extensionTools\Traits\Command\InitializeTrait;
use DWenzel\T3extensionTools\Traits\Command\OptionAwareTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

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
 * Provides import set commands for cli and scheduler tasks
 */
class ImportTaskCommand extends Command implements ArgumentAwareInterface
{
    use ConfigureTrait,
        InitializeTrait,
        ArgumentAwareTrait,
        TransferCommandTrait;

    /**
     * Key under which configuration are found in
     * Framework configuration.
     * This should match the key for the ImportController
     */
    const SETTINGS_KEY = ImportController::SETTINGS_KEY;

    public const DEFAULT_NAME = 't3import-export:import-task';
    public const MESSAGE_DESCRIPTION_COMMAND = 'Performs pre-defined import task.';
    public const MESSAGE_HELP_COMMAND = '@todo: help command';
    public const MESSAGE_SUCCESS = 'Import task successfully processed';
    public const MESSAGE_STARTING = 'Starting import task';
    public const WARNING_MISSING_PARAMETER = 'Parameter "%s" must not be omitted';
    public const WARNING_MISSING_CONFIGURATION = 'No configuration found for task with identifier "%s".';
    protected const OPTIONS = [];
    protected const ARGUMENTS = [
        TaskArgument::class
    ];

    static protected $optionsToConfigure = self::OPTIONS;
    static protected $argumentsToConfigure = self::ARGUMENTS;
    /**
     * @var string
     */
    protected static $defaultName = self::DEFAULT_NAME;
    protected TransferTaskFactory $transferTaskFactory;

    /**
     * TransferCommandTrait constructor.
     * @param string|null $name
     * @param TransferTaskFactory|null $transferTaskFactory
     * @param TransferSetFactory|null $transferSetFactory
     * @param DataTransferProcessor|null $dataTransferProcessor
     */
    public function __construct(
        string $name = null,
        TransferTaskFactory $transferTaskFactory = null,
        DataTransferProcessor $dataTransferProcessor = null,
        ConfigurationManagerInterface $configurationManager = null
    )
    {
        $this->transferTaskFactory = $transferTaskFactory ?? GeneralUtility::makeInstance(TransferTaskFactory::class);
        $this->dataTransferProcessor = $dataTransferProcessor ?? GeneralUtility::makeInstance(DataTransferProcessor::class);
        $this->configurationManager = $configurationManager ?? GeneralUtility::makeInstance(ConfigurationManager::class);
        parent::__construct($name);
        $this->initializeObject();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->comment(self::MESSAGE_STARTING);
        $identifier = (string)$input->getArgument(TaskArgument::NAME);

        $status = $this->assertValidIdentifier($identifier);

        if(empty($this->settings['tasks'][$identifier])) {
            $this->io->warning(
                sprintf(self::WARNING_MISSING_CONFIGURATION, $identifier)
            );

            $status = Command::INVALID;
        }

        $status = $this->process($identifier);
        if ($status === 0) {
            $this->io->success(self::MESSAGE_SUCCESS);
            return Command::SUCCESS;
        }

        $this->io->error('An error occurred');
        return $status;
    }


    /**
     * Processes predefined import sets
     *
     * @param string $identifier Identifier of set which should be processed
     * @param bool $dryRun If set nothing will be saved
     * @return void
     * @throws InvalidConfigurationException
     * @throws MissingClassException
     * @throws MissingInterfaceException
     * @return int Returns an exit code
     * @see Command exit codes
     */
    public function process(string $identifier, $dryRun = false): int
    {
        $status = Command::INVALID;
        /** @var TaskDemand $taskDemand */
        $taskDemand = GeneralUtility::makeInstance(TaskDemand::class);

        if (isset($this->settings['tasks'][$identifier])) {
            $taskSettings = $this->settings['tasks'][$identifier];

            $task = $this->transferTaskFactory->get(
                $taskSettings, $identifier
            );

            $taskDemand->setTasks([$task]);
            $this->dataTransferProcessor->buildQueue($taskDemand);
            $result = $this->dataTransferProcessor->process($taskDemand);
            $status = Command::SUCCESS;
            // todo check result for error and set exit code accordingly
        }

        return $status;
    }

    /**
     * @param string $identifier
     */
    protected function assertValidIdentifier(string $identifier): int
    {
        $status = Command::SUCCESS;
        if (empty($identifier)) {
            $this->io->warning(
                sprintf(
                    static::WARNING_MISSING_PARAMETER,
                    SetArgument::NAME
                )
            );

            $status = Command::INVALID;
        }

        return $status;
    }

}
