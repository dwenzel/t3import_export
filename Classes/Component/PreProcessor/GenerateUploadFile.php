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
use CPSIT\T3importExport\ResourceStorageTrait;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class GenerateUploadFile
 */
class GenerateUploadFile extends AbstractPreProcessor implements PreProcessorInterface
{
    use ResourceStorageTrait;

    /**
     * Process file upload
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
                $singleValue = $this->generateFilePath($configuration, $filePath);
                $fieldValue[] = $singleValue;
            }

        } else {
            $fieldValue = $this->generateFilePath($configuration, $filePaths[0]);
        }

        $record[$configuration['fieldName']] = $fieldValue;

        return true;
    }


    /**
     * Generates a file path for a TYPO3 upload file field
     * (where the file name is stored and a path is prefixed - usually something like 'uploads/<extensionName>')
     *
     * @param array $configuration A valid configuration containing at least a targetDirectoryPath
     * @param string $sourceFilePath
     * @return string Returns a valid entry for a file upload field or an empty string
     */
    public function generateFilePath($configuration, $sourceFilePath)
    {
        $filePath = '';
        $pathParts = pathinfo($sourceFilePath);

        $storageConfiguration = $this->resourceStorage->getConfiguration();

        $targetDirectoryPath = rtrim(GeneralUtility::getFileAbsFileName($storageConfiguration['basePath']),
                '/') . $configuration['targetDirectoryPath'];

        if (@copy($sourceFilePath, $targetDirectoryPath . $pathParts['basename'])) {
            $filePath = $storageConfiguration['basePath'] . ltrim($configuration['targetDirectoryPath'] . $pathParts['basename'], '/\\');
        }

        return $filePath;
    }

    /**
     * Check configuration
     *
     * @param array $configuration
     * @return bool
     */
    public function isConfigurationValid(array $configuration)
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
}