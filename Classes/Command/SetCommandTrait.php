<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Command;

use CPSIT\ImportExportCore\Configuration\ConfigurationManager as YamlConfigurationManager;
use CPSIT\ImportExportCore\Configuration\YamlConfigurationLoader;
use CPSIT\ImportExportCore\Exception\InvalidConfigurationException;
use CPSIT\ImportExportCore\Exception\MissingClassException;
use CPSIT\ImportExportCore\Exception\MissingInterfaceException;
use CPSIT\T3importExport\Command\Argument\SetArgument;
use CPSIT\T3importExport\Command\Option\YamlConfigFileOption;
use CPSIT\T3importExport\Domain\Factory\TransferSetFactory;
use CPSIT\T3importExport\Domain\Model\Dto\TaskDemand;
use CPSIT\T3importExport\Service\DataTransferProcessor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use CPSIT\ImportExportCore\Configuration\ConfigurationManagerInterface;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2022 Dirk Wenzel <wenzel@cps-it.de>
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
trait SetCommandTrait
{
    use TransferCommandTrait;

    /**
     * TransferCommandTrait constructor.
     */
    public function __construct(
        private readonly TransferSetFactory    $transferSetFactory,
        private readonly DataTransferProcessor $dataTransferProcessor,
        private readonly ConfigurationManagerInterface $configurationManager
    ) {
        $this->initializeObject();
    }

    /**
     * Import set command
     * Performs predefined import sets
     *
     * @param string $identifier Identifier of set which should be performed
     * @param bool $dryRun If set nothing will be saved
     * @throws InvalidConfigurationException
     * @throws MissingClassException
     * @throws MissingInterfaceException
     */
    public function process(string $identifier, bool $dryRun = false): void
    {
        /** @var TaskDemand $demand */
        $demand = GeneralUtility::makeInstance(TaskDemand::class);

        if (isset($this->settings['sets'][$identifier])) {
            $set = $this->transferSetFactory->get(
                $this->settings['sets'][$identifier],
                $identifier
            );
            $demand->setTasks($set->getTasks());
            $this->dataTransferProcessor->buildQueue($demand);
            if (!$dryRun) {
                $this->dataTransferProcessor->process($demand);
            }
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $identifier = (string)$input->getArgument(SetArgument::NAME);
        if (empty($identifier)) {
            $this->io->warning(
                sprintf(
                    static::WARNING_MISSING_PARAMETER,
                    SetArgument::NAME
                )
            );

            return Command::INVALID;
        }

        // Check if YAML configuration file is provided
        $yamlConfigFile = $input->getOption(YamlConfigFileOption::NAME);
        if (!empty($yamlConfigFile)) {
            $this->loadYamlConfiguration($yamlConfigFile);
        }

        $this->io->comment(static::MESSAGE_STARTING);

        $this->process($identifier);
        $this->io->success(static::MESSAGE_SUCCESS);

        return Command::SUCCESS;
    }

    /**
     * Load YAML configuration from file
     *
     * @param string $yamlFile Path to YAML configuration file
     */
    protected function loadYamlConfiguration(string $yamlFile): void
    {
        $yamlLoader = GeneralUtility::makeInstance(YamlConfigurationLoader::class);
        $configManager = GeneralUtility::makeInstance(YamlConfigurationManager::class);

        try {
            $configManager->addConfiguration($yamlLoader, $yamlFile);
            $config = $configManager->getFullConfiguration();

            if (isset($config['module']['tx_t3importexport']['settings'][static::SETTINGS_KEY])) {
                // Merge YAML configuration with existing TypoScript configuration
                $this->settings = array_merge_recursive(
                    $this->settings ?? [],
                    $config['module']['tx_t3importexport']['settings'][static::SETTINGS_KEY]
                );
            }
        } catch (\Exception $e) {
            $this->io->error('Error loading YAML configuration: ' . $e->getMessage());
        }
    }
}
