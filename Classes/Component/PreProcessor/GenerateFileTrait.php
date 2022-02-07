<?php

namespace CPSIT\T3importExport\Component\PreProcessor;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use CPSIT\T3importExport\Factory\FilePathFactory;
use CPSIT\T3importExport\LoggingTrait;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use CPSIT\T3importExport\Resource\ResourceStorageTrait;

/**
 * Trait GenerateFileTrait
 */
trait GenerateFileTrait
{
    use ResourceStorageTrait, LoggingTrait;

    /**
     * Errors by id
     *
     * @var array
     */
    protected static $errors = [
        1499007587 => ['Empty configuration', 'Configuration must not be empty'],
        1497427302 => ['Missing storage id', 'config[\'storageId\'] must be set'],
        1497427320 => ['Missing target directory ', 'config[\'targetDirectoryPath\` must be set'],
        1497427335 => ['Missing field name', 'config[\'fieldName\'] must be set'],
        1497427346 => ['Invalid storage', 'Could not find storage with id %s given in $config[\'storageId\']'],
        1497427363 => ['Missing directory', 'Directory %s given in $config[\'basePath\'] and $config[\'targetDirectory\'] does not exist.']
    ];

    /**
     * @var FilePathFactory
     */
    protected FilePathFactory $filePathFactory;

    /**
     * injects the file path factory
     * @param FilePathFactory $factory
     * @deprecated
     */
    public function injectFilePathFactory(FilePathFactory $factory){
        $this->filePathFactory = $factory;
    }

    /**
     * Get File object
     * Method must fetch the file and return the correct value for the target field (either a file object or string or null).
     *
     * @param array $configuration
     * @param string $sourceFilePath
     * @return \TYPO3\CMS\Core\Resource\File|string|null
     */
    abstract public function getFile($configuration, $sourceFilePath);

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
        return static::$errors;
    }

    /**
     * Process record
     * Generates one or multiple file objects, adds them to the repository and the record field
     *
     * @param array $configuration
     * @param array $record
     * @return bool
     */
    public function process($configuration, &$record)
    {
        $separator = ',';
        if (isset($configuration['separator'])) {
            $separator = $configuration['separator'];
        }
        $filePaths = GeneralUtility::trimExplode($separator, $record[$configuration['fieldName']], true);

        // Prefix all files with source path
        if (isset($configuration['sourcePath'])) {
            $filePaths = preg_filter('/^/', $configuration['sourcePath'], $filePaths);
        }

        if ($configuration['multipleRows']) {
            $fieldValue = [];

            foreach ($filePaths as $filePath) {
                $singleValue = $this->getFile($configuration, $filePath);
                $fieldValue[] = $singleValue;
            }
        } else {
            $fieldValue = $this->getFile($configuration, $filePaths[0]);
        }

        $record[$configuration['fieldName']] = $fieldValue;

        return true;
    }

    /**
     * Check configuration
     *
     * @param array $configuration
     * @return bool
     */
    public function isConfigurationValid(array $configuration): bool
    {
        if (empty($configuration)) {
            $this->logError(1499007587);
            return false;
        }
        if (!isset($configuration['targetDirectoryPath'])) {
            $this->logError(1497427320);
            return false;
        }

        if (!isset($configuration['fieldName'])) {
            $this->logError(1497427335);
            return false;
        }

        if (!isset($configuration['storageId'])) {
            $this->logError(1497427302);
            return false;
        }

        $this->initializeStorage($configuration);

        if (!$this->resourceStorage instanceof ResourceStorage) {
            $this->logError(1497427346, [$configuration['storageId']]);
            return false;
        }

        if (!$this->resourceStorage->hasFolder($configuration['targetDirectoryPath'])) {
            $storageConfiguration = $this->resourceStorage->getConfiguration();
            $this->logError(1497427363, [$storageConfiguration['basePath'] . ltrim($configuration['targetDirectoryPath'], '/\\')]);

            return false;
        }

        return true;
    }

    /**
     * @param array $configuration
     * @param string $sourcePath
     * @return string
     */
    protected function getTargetPath($configuration, $sourcePath)
    {
        $storageConfiguration = $this->resourceStorage->getConfiguration();

        $targetDirectoryPath = $this->filePathFactory->createFromParts(
            [
                $storageConfiguration['basePath'],
                $configuration['targetDirectoryPath'],
            ]
        );

        return $targetDirectoryPath . PathUtility::basename($sourcePath);
    }
}
