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

use CPSIT\ImportExportCore\Configuration\ConfigurationHandlerInterface;
use CPSIT\ImportExportCore\Domain\Model\TransferSet;
use CPSIT\ImportExportCore\Exception\InvalidConfigurationException;
use CPSIT\ImportExportCore\Factory\TransferSetFactory as CoreTransferSetFactory;

/**
 * Builds sets with the factory of the core. The tasks of a set are looked up
 * in the import and export tasks of the configuration handler (YAML); import
 * tasks take precedence.
 */
class TransferSetFactory
{
    public function __construct(
        private readonly CoreTransferSetFactory $transferSetFactory,
        private readonly ConfigurationHandlerInterface $configurationHandler
    ) {}

    /**
     * @throws InvalidConfigurationException if a task of the set is not defined or its configuration is invalid
     */
    public function get(array $settings, string $identifier): TransferSet
    {
        // Read on each call: configuration may be added after construction (e.g. --yaml-config).
        // Structure: ['import' => ['tasks' => ..., 'sets' => ...], 'export' => [...]]
        $configuration = $this->configurationHandler->getFullConfiguration();
        $taskDefinitions = array_replace(
            is_array($configuration['export']['tasks'] ?? null) ? $configuration['export']['tasks'] : [],
            is_array($configuration['import']['tasks'] ?? null) ? $configuration['import']['tasks'] : []
        );

        return $this->transferSetFactory->create($identifier, $settings, $taskDefinitions);
    }
}
