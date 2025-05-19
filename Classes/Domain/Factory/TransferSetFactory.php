<?php

declare(strict_types=1);

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
use CPSIT\T3importExport\MissingClassException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

/**
 * Class TransferSetFactory
 * builds import sets from settings
 */
class TransferSetFactory extends AbstractFactory
{
    public function __construct(
        protected TransferTaskFactory $transferTaskFactory,
        protected ConfigurationManagerInterface $configurationManager,
        protected TransferSet $transferSet
    ) {
        $extensionConfiguration = $configurationManager->getConfiguration(
            ConfigurationManager::CONFIGURATION_TYPE_FRAMEWORK,
            't3importexport'
        );
        $this->settings = $extensionConfiguration['settings'] ?? [];
    }

    /**
     * Builds a set of tasks
     *
     * @param array $settings
     * @param string|null $identifier
     * @return TransferSet
     * @throws InvalidConfigurationException
     * @throws MissingClassException
     * @throws MissingInterfaceException|\CPSIT\T3importExport\MissingInterfaceException
     */
    public function get(array $settings = [], ?string $identifier = null): object
    {
        // clone object to prevent from returning the same object on later calls
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
                        $this->settings['import']['tasks'][$taskIdentifier],
                        $taskIdentifier
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
