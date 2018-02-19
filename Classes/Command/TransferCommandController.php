<?php
namespace CPSIT\T3importExport\Command;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2018 Dirk Wenzel <wenzel@cps-it.de>
 *  All rights reserved
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * A copy is found in the text file GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use CPSIT\T3importExport\Domain\Factory\TransferSetFactory;
use CPSIT\T3importExport\Domain\Factory\TransferTaskFactory;
use CPSIT\T3importExport\Domain\Model\Dto\TaskDemand;
use CPSIT\T3importExport\Service\DataTransferProcessor;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;

/**
 * Class TransferCommandController
 */
class TransferCommandController extends CommandController
{
    /**
     * Key under which configuration are found in
     * Framework configuration.
     * There is no automatism for retrieving the proper TypoScript configuration of command controllers.
     */
    const SETTINGS_KEY = 'transfer';

    /**
     * @var array
     */
    protected $settings;
    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;
    /**
     * @var \CPSIT\T3importExport\Domain\Factory\TransferTaskFactory
     */
    protected $transferTaskFactory;
    /**
     * @var \CPSIT\T3importExport\Domain\Factory\TransferSetFactory
     */
    protected $transferSetFactory;
    /**
     * @var DataTransferProcessor
     */
    protected $dataTransferProcessor;

    /**
     * Injects the configuration manager and loads the TypoScript settings
     *
     * @param ConfigurationManager $configurationManager
     */
    public function injectConfigurationManager(ConfigurationManager $configurationManager)
    {
        $this->configurationManager = $configurationManager;
    }

    /**
     * Injects the import processor
     *
     * @param DataTransferProcessor $dataTransferProcessor
     */
    public function injectDataTransferProcessor(DataTransferProcessor $dataTransferProcessor)
    {
        $this->dataTransferProcessor = $dataTransferProcessor;
    }

    /**
     * Injects the import task factory
     *
     * @param TransferTaskFactory $transferTaskFactory
     */
    public function injectTransferTaskFactory(TransferTaskFactory $transferTaskFactory)
    {
        $this->transferTaskFactory = $transferTaskFactory;
    }

    /**
     * Injects the import set factory
     *
     * @param TransferSetFactory $transferSetFactory
     */
    public function injectTransferSetFactory(TransferSetFactory $transferSetFactory)
    {
        $this->transferSetFactory = $transferSetFactory;
    }

    /**
     * Import task command
     * Performs predefined import tasks
     *
     * @param string $identifier Identifier of task which should be performed
     * @param bool $dryRun If set nothing will be saved
     * @throws \CPSIT\T3importExport\MissingInterfaceException
     * @throws \CPSIT\T3importExport\MissingClassException
     * @throws \CPSIT\T3importExport\InvalidConfigurationException
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
            $this->dataTransferProcessor->buildQueue($importDemand);
            if (!$dryRun) {
                $this->dataTransferProcessor->process($importDemand);
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
     * @throws \CPSIT\T3importExport\InvalidConfigurationException
     */
    public function setCommand($identifier, $dryRun = false)
    {
        /** @var TaskDemand $importDemand */
        $importDemand = $this->objectManager->get(
            TaskDemand::class
        );

        if (isset($this->settings['sets'][$identifier])) {
            $set = $this->transferSetFactory->get(
                $this->settings['sets'][$identifier], $identifier
            );
            $importDemand->setTasks($set->getTasks());
            $this->dataTransferProcessor->buildQueue($importDemand);
            if (!$dryRun) {
                $this->dataTransferProcessor->process($importDemand);
            }
        }
    }

    /**
     * initialize object
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    public function initializeObject()
    {
        $extbaseFrameworkConfiguration = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK
        );

        if (isset($extbaseFrameworkConfiguration['settings'][static::SETTINGS_KEY])) {
            $this->settings = $extbaseFrameworkConfiguration['settings'][static::SETTINGS_KEY];
        }
    }
}
