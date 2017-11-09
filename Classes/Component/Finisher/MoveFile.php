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
use CPSIT\T3importExport\Domain\Model\TaskResult;
use CPSIT\T3importExport\LoggingInterface;
use CPSIT\T3importExport\LoggingTrait;
use CPSIT\T3importExport\Resource\ResourceFactoryTrait;
use CPSIT\T3importExport\Resource\ResourceStorageTrait;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Class MoveFileFromStream
 */
class MoveFile extends AbstractFinisher
    implements FinisherInterface, ConfigurableInterface, LoggingInterface
{
    use LoggingTrait, ResourceFactoryTrait, ResourceStorageTrait;

    /**
     * cancel file operation
     */
    const CONFLICT_MODE_CANCEL = 'cancel';

    /**
     * change name of new file according to TYPO3 conventions
     */
    const CONFLICT_MODE_RENAME_NEW_FILE = 'renameNewFile';

    /**
     * replace existing file
     */
    const CONFLICT_MODE_OVERRIDE_EXISTING_FILE = 'overrideExistingFile';

    /**
     * Valid values for conflict modes (for operations on new file)
     */
    const CONFLICT_MODES = [
        self::CONFLICT_MODE_CANCEL,
        self::CONFLICT_MODE_RENAME_NEW_FILE,
        self::CONFLICT_MODE_OVERRIDE_EXISTING_FILE
    ];

    /**
     * Error by id
     * <unique id> => ['title', ['message']
     */
    const ERROR_CODES = [
        1509011717 => ['Empty configuration', 'Configuration must not be empty'],
        1509011925 => ['Missing target', 'config.target.name. must be a string'],
        1509022342 => ['Missing source', 'config.source.name. must be a string'],
        1509023738 => ['Missing source file', 'File %1s could not be found.'],
    ];

    /**
     * Notice by id
     * <unique id> => ['title', ['message']
     */
    const NOTICE_CODES = [
        1509024162 => ['File moved', 'File %1s has been moved succesfully to %2s.'],
    ];

    /**
     * Returns error codes for current component.
     * Must be an array in the form
     * [
     *  <id> => ['errorTitle', 'errorDescription']
     * ]
     * 'errorDescription' may contain placeholder (%s) for arguments.
     * @return array
     */
    public function getErrorCodes()
    {
        return self::ERROR_CODES;
    }

    /**
     * Returns notice codes for current component.
     * Must be an array in the form
     * [
     *  <id> => ['title', 'description']
     * ]
     * 'description' may contain placeholder (%s) for arguments.
     * @return array
     */
    public function getNoticeCodes()
    {
        return static::NOTICE_CODES;
    }

    /**
     * Tells whether the given configuration is valid
     *
     * @param array $configuration
     * @return bool
     */
    public function isConfigurationValid(array $configuration)
    {
        if (empty($configuration)) {
            $this->logError(1509011717);
            return false;
        }
        if (
            !isset($configuration['target']['name'])
            || empty($configuration['target']['name'])
            || !is_string($configuration['target']['name'])
        ) {
            $this->logError(1509011925);
            return false;
        }
        if (
             !isset($configuration['source']['name'])
                || empty($configuration['source']['name'])
             || !is_string($configuration['source']['name'])
        ) {
            $this->logError(1509022342);
            return false;
        }
        if (!empty($configuration['target']['storage']) && !MathUtility::canBeInterpretedAsInteger($configuration['target']['storage'])) {
            return false;
        }
        if (!empty($configuration['source']['storage']) && !MathUtility::canBeInterpretedAsInteger($configuration['source']['storage'])) {
            return false;
        }
        if (!empty($configuration['target']['directory']) && !is_string($configuration['target']['directory'])) {
            return false;
        }
        if (!empty($configuration['source']['directory']) && !is_string($configuration['source']['directory'])) {
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
     * move file from source to
     * If no storage or folder is configured, the file is written
     * to the default folder in the default storage.
     * If a file with target file name already exists the conflictMode
     * determines the result: cancel, renameNewFile, overrideExistingFile are allowed.
     * Default is renameNewFile (according to TYPO3 conventions)
     * @param array $configuration
     * @param array $records
     * @param array|TaskResult $result
     * @return bool Returns false if the result is not a TaskResult or doesn't contain a FileInfo object.
     */
    public function process($configuration, &$records, &$result)
    {
        $defaultStorage = $this->resourceFactory->getDefaultStorage();
        $targetStorage = $defaultStorage;
        $sourceStorage = $defaultStorage;
        $sourceFileName = $configuration['source']['name'];

        if (!empty($configuration['source']['storage'])) {
            $sourceStorage = $this->resourceFactory->getStorageObject((int)$configuration['source']['storage']);
        }

        $sourceFolder = $sourceStorage->getDefaultFolder();

        if (isset($configuration['source']['directory'])) {
            $sourceDirectory = $configuration['source']['directory'];
            if ($sourceStorage->hasFolder($sourceDirectory)) {
                $sourceFolder = $sourceStorage->getFolder($sourceDirectory);
            }
        }

        if (!$sourceStorage->hasFileInFolder($sourceFileName, $sourceFolder)) {
            $this->logError(1509023738, [$sourceFileName], $configuration);
            return false;
        }

        $filesInSourceFolder = $sourceFolder->getFiles();
        $sourceFile = $filesInSourceFolder[$sourceFileName];

        if (!empty($configuration['target']['storage'])) {
            $targetStorage = $this->resourceFactory->getStorageObject((int)$configuration['target']['storage']);
        }

        $targetFolder = $targetStorage->getDefaultFolder();
        if (isset($configuration['target']['directory'])) {
            $targetDirectory = $configuration['target']['directory'];
            if (!$targetStorage->hasFolder($targetDirectory)) {
                $targetFolder = $targetStorage->createFolder($targetDirectory);
            } else {
                $targetFolder = $targetStorage->getFolder($targetDirectory);
            }
        }

        $conflictMode = self::CONFLICT_MODE_RENAME_NEW_FILE;
        if (!empty($configuration['target']['conflictMode'])) {
            $conflictMode = $configuration['target']['conflictMode'];
        }

        $targetFileName = $configuration['target']['name'];
        $sourceStorage->moveFile(
            $sourceFile,
            $targetFolder,
            $targetFileName,
            $conflictMode
        );
        $this->logNotice(1509024162, [$sourceFileName, $targetFileName], $configuration);

        return true;
    }
}
