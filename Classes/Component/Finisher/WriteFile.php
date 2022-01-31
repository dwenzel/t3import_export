<?php

namespace CPSIT\T3importExport\Component\Finisher;

/***************************************************************
 *  Copyright notice
 *  (c) 2016 Dirk Wenzel <dirk.wenzel@cps-it.de>
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

use CPSIT\T3importExport\ConfigurableInterface;
use CPSIT\T3importExport\Domain\Model\Dto\FileInfo;
use CPSIT\T3importExport\Domain\Model\TaskResult;
use CPSIT\T3importExport\Resource\ResourceFactoryTrait;
use CPSIT\T3importExport\Resource\ResourceStorageTrait;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Class WriteFileFromStream
 */
class WriteFile extends AbstractFinisher implements FinisherInterface, ConfigurableInterface
{
    use ResourceFactoryTrait, ResourceStorageTrait;

    /**
     * cancel file operation
     */
    const CONFLICT_MODE_CANCEL = 'cancel';

    /**
     * change name of new file according to TYPO3 conventions
     */
    const CONFLICT_MODE_CHANGENAME = 'changeName';

    /**
     * replace existing file
     */
    const CONFLICT_MODE_REPLACE = 'replace';

    /**
     * Valid values for conflict modes (for file operations)
     */
    const CONFLICT_MODES = [
        self::CONFLICT_MODE_CANCEL,
        self::CONFLICT_MODE_CHANGENAME,
        self::CONFLICT_MODE_REPLACE
    ];

    /**
     * Tells whether the given configuration is valid
     *
     * @param array $configuration
     * @return bool
     */
    public function isConfigurationValid(array $configuration): bool
    {
        if (
            empty($configuration)
            || (isset($configuration['target'])
                && is_array($configuration['target'])
                && (empty($configuration['target']['name']) || !is_string($configuration['target']['name'])))
        ) {
            return false;
        }
        if (!empty($configuration['target']['storage']) && !MathUtility::canBeInterpretedAsInteger($configuration['target']['storage'])) {
            return false;
        }
        if (!empty($configuration['target']['directory']) && !is_string($configuration['target']['directory'])) {
            return false;
        }
        if (isset($configuration['target']['conflictMode'])) {
            $conflictMode = $configuration['target']['conflictMode'];
            if (
                empty($conflictMode)
                || !is_string($conflictMode)
                || !in_array($conflictMode, self::CONFLICT_MODES)
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Process the result:
     * write file from fileInfo field of
     * TaskResult object to a configured storage/folder/file name
     * If no storage or folder is configured, the file is written
     * to the default folder in the default storage.
     * If a file with target file name already exists the conflictMode
     * determines the result: cancel, rename, replace are allowed.
     * Default is rename (according to TYPO3 conventions)
     * @param array $configuration
     * @param array $records
     * @param array|TaskResult $result
     * @return bool Returns false if the result is not a TaskResult or doesn't contain a FileInfo object.
     */
    public function process(array $configuration, array &$records, &$result): bool
    {
        if (
        !($result instanceof TaskResult && $result->getInfo() instanceof FileInfo)
        ) {
            return false;
        }

        /** @var FileInfo $fileInfo */
        $fileInfo = $result->getInfo();

        if (!empty($configuration['target']['storage'])) {
            $storage = $this->resourceFactory->getStorageObject((int)$configuration['target']['storage']);
        } else {
            $storage = $this->resourceFactory->getDefaultStorage();
        }
        $folder = $storage->getDefaultFolder();
        if (isset($configuration['target']['directory'])) {
            $targetDirectory = $configuration['target']['directory'];
            if (!$storage->hasFolder($targetDirectory)) {
                $folder = $storage->createFolder($targetDirectory);
            } else {
                $folder = $storage->getFolder($targetDirectory);
            }
        }

        $conflictMode = self::CONFLICT_MODE_CHANGENAME;
        if (!empty($configuration['target']['conflictMode'])) {
            $conflictMode = $configuration['target']['conflictMode'];
        }

        $storage->addFile(
            $fileInfo->getRealPath(),
            $folder,
            $configuration['target']['name'],
            $conflictMode
        );

        return true;
    }
}
