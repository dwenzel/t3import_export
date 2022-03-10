<?php

namespace CPSIT\T3importExport\Domain\Factory;

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

use CPSIT\T3importExport\Domain\Model\TransferSet;
use CPSIT\T3importExport\Factory\AbstractFactory;
use CPSIT\T3importExport\InvalidConfigurationException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class TransferSetFactory
 * builds import sets from settings
 *
 * @package CPSIT\T3importExport\Domain\Repository
 */
class TransferSetFactory extends AbstractFactory
{

    protected TransferTaskFactory $transferTaskFactory;
    protected TransferSet $transferSet;

    public function __construct(
        TransferTaskFactory $transferTaskFactory = null,
        ConfigurationManagerInterface $configurationManager = null,
        TransferSet $transferSet = null)
    {

        if (null === $transferTaskFactory) {
            $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

            /** @var TransferTaskFactory $transferTaskFactory */
            $transferTaskFactory = $objectManager->get(TransferTaskFactory::class);
        }

        if (null == $configurationManager) {
            $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
            /** @var ConfigurationManager $configurationManager */
            $configurationManager = $objectManager->get(ConfigurationManagerInterface::class);
        }
        $this->settings = $configurationManager->getConfiguration(
            ConfigurationManager::CONFIGURATION_TYPE_SETTINGS
        );
        /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
        $this->transferTaskFactory = $transferTaskFactory;
        $this->transferSet = $transferSet ?? GeneralUtility::makeInstance(TransferSet::class);
    }

    /**
     * Builds a set of tasks
     *
     * @param array $settings
     * @param string $identifier
     * @return TransferSet
     * @throws InvalidConfigurationException
     */
    public function get(array $settings, $identifier = null)
    {
        // clone object in order to prevent from returning the same object on subsequent calls
        // transferSet must be injected for testing purposes
        $this->transferSet = clone $this->transferSet;
        $this->transferSet->setIdentifier($identifier);

        if (isset($settings['tasks'])
            && is_string($settings['tasks'])
        ) {
            $taskIdentifiers = GeneralUtility::trimExplode(',', $settings['tasks'], true);
            $tasks = [];
            foreach ($taskIdentifiers as $taskIdentifier) {
                if (isset($this->settings['import']['tasks'][$taskIdentifier])) {
                    $task = $this->transferTaskFactory->get(
                        $this->settings['import']['tasks'][$taskIdentifier], $taskIdentifier
                    );
                    $tasks[$taskIdentifier] = $task;
                }
            }
            $this->transferSet->setTasks($tasks);
        }

        if (isset($settings['description'])
            && is_string($settings['description'])
        ) {
            $this->transferSet->setDescription($settings['description']);
        }

        if (isset($settings['label'])) {
            $this->transferSet->setLabel($settings['label']);
        }

        return $this->transferSet;
    }
}
