<?php

namespace CPSIT\T3importExport\Command;

use CPSIT\T3importExport\Command\Argument\SetArgument;
use CPSIT\T3importExport\Domain\Factory\TransferSetFactory;
use CPSIT\T3importExport\Domain\Model\Dto\TaskDemand;
use CPSIT\T3importExport\InvalidConfigurationException;
use CPSIT\T3importExport\Service\DataTransferProcessor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

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

    protected TransferSetFactory $transferSetFactory;


    /**
     * TransferCommandTrait constructor.
     * @param string|null $name
     * @param TransferSetFactory|null $transferSetFactory
     * @param DataTransferProcessor|null $dataTransferProcessor
     */
    public function __construct(
        string $name = null,
        TransferSetFactory $transferSetFactory = null,
        DataTransferProcessor $dataTransferProcessor = null,
        ConfigurationManagerInterface $configurationManager = null

    )
    {
        $this->transferSetFactory = $transferSetFactory ?? GeneralUtility::makeInstance(TransferSetFactory::class);
        $this->dataTransferProcessor = $dataTransferProcessor ?? GeneralUtility::makeInstance(DataTransferProcessor::class);
        $this->configurationManager = $configurationManager ?? GeneralUtility::makeInstance(ConfigurationManager::class);

        parent::__construct($name);
        $this->initializeObject();
    }

    /**
     * Import set command
     * Performs predefined import sets
     *
     * @param string $identifier Identifier of set which should be performed
     * @param bool $dryRun If set nothing will be saved
     * @return void
     * @throws InvalidConfigurationException
     */
    public function process($identifier, $dryRun = false): void
    {
        /** @var TaskDemand $demand */
        $demand = GeneralUtility::makeInstance(TaskDemand::class);

        if (isset($this->settings['sets'][$identifier])) {
            $set = $this->transferSetFactory->get(
                $this->settings['sets'][$identifier], $identifier
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

            // @todo this is a workaround for TYPO3 9.5 where the class constant is not defined
            return (defined(Command::class . '::INVALID')? Command::INVALID : 2);
        }
        $this->io->comment(static::MESSAGE_STARTING);

        $this->process($identifier);
        $this->io->success(static::MESSAGE_SUCCESS);

        // @todo this is a workaround for TYPO3 9.5 where the class constant is not defined
        return (defined(Command::class . '::SUCCESS')? Command::SUCCESS : 0);
    }
}
