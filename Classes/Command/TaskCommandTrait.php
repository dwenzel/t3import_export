<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Command;

use CPSIT\ImportExportCore\Configuration\ConfigurationHandlerInterface;
use CPSIT\ImportExportCore\Exception\InvalidConfigurationException;
use CPSIT\ImportExportCore\Exception\MissingClassException;
use CPSIT\ImportExportCore\Exception\MissingInterfaceException;
use CPSIT\ImportExportCore\Factory\TransferTaskFactory;
use CPSIT\T3importExport\Command\Argument\TaskArgument;
use CPSIT\T3importExport\Command\Option\YamlConfigFileOption;
use CPSIT\T3importExport\Service\TransferService;
use DWenzel\T3extensionTools\Command\Status;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2026 Dirk Wenzel <wenzel@cps-it.de>
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

/**
 * Runs a single pre-defined task from the settings below static::SETTINGS_KEY
 */
trait TaskCommandTrait
{
    use TransferCommandTrait;

    public function __construct(
        private readonly TransferTaskFactory $transferTaskFactory,
        private readonly TransferService $transferService,
        private readonly ConfigurationHandlerInterface $configurationHandler
    ) {
        parent::__construct(self::DEFAULT_NAME);
        $this->initializeObject();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $identifier = (string)$input->getArgument(TaskArgument::NAME);
        if (empty($identifier)) {
            $this->io->warning(
                sprintf(static::WARNING_MISSING_PARAMETER, TaskArgument::NAME)
            );

            return Status::invalid();
        }

        // Only commands which register the option can load a YAML configuration file
        if ($input->hasOption(YamlConfigFileOption::NAME)) {
            $yamlConfigFile = (string)$input->getOption(YamlConfigFileOption::NAME);
            if ($yamlConfigFile !== '' && !$this->loadYamlConfiguration($yamlConfigFile)) {
                return Status::failure();
            }
        }

        $this->io->comment(static::MESSAGE_STARTING);

        if (empty($this->settings['tasks'][$identifier])) {
            $this->io->warning(
                sprintf(static::WARNING_MISSING_CONFIGURATION, $identifier)
            );

            return Status::invalid();
        }

        try {
            $status = $this->process($identifier);
        } catch (InvalidConfigurationException | MissingInterfaceException | MissingClassException $exception) {
            $this->io->error(
                'An error occurred: ' . $exception->getMessage() . ' code: ' . $exception->getCode()
            );

            return Status::failure();
        }

        if ($status === Status::success()) {
            $this->io->success(static::MESSAGE_SUCCESS);
        }

        return $status;
    }

    /**
     * Processes a pre-defined task
     *
     * @param string $identifier Identifier of the task which should be processed
     * @param bool $dryRun If set nothing will be saved
     * @throws InvalidConfigurationException
     * @throws MissingClassException
     * @throws MissingInterfaceException
     * @return int Returns an exit code
     * @see Command exit codes
     */
    public function process(string $identifier, bool $dryRun = false): int
    {
        if (!isset($this->settings['tasks'][$identifier])) {
            return Status::invalid();
        }

        $task = $this->transferTaskFactory->create(
            $identifier,
            $this->settings['tasks'][$identifier]
        );
        $this->transferService->run([$task], $dryRun);

        return Status::success();
    }
}
