<?php

declare(strict_types=1);

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
use CPSIT\ImportExportCore\Exception\MissingResourceException;
use CPSIT\ImportExportCore\LoggingTrait;
use CPSIT\ImportExportCore\Resource\HttpResourceLoader;
use CPSIT\T3importExport\Factory\FilePathFactory;
use CPSIT\T3importExport\Resource\ResourceAccess;
use CPSIT\T3importExport\Resource\ResourceStorageTrait;
use CPSIT\T3importExport\Resource\ResourceTrait;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Trait GenerateFileTrait
 */
trait GenerateFileTrait
{
    use ResourceStorageTrait;
    use ResourceTrait;
    use LoggingTrait;

    protected FilePathFactory $filePathFactory;

    /**
     * injects the file path factory
     * @deprecated
     */
    public function injectFilePathFactory(FilePathFactory $factory)
    {
        $this->filePathFactory = $factory;
    }

    /**
     * Get File object
     * Method must fetch the file and return the correct value for the target field (either a file object or string or null).
     *
     * @param array $configuration
     * @param string $sourceFilePath
     * @return File|string|null
     */
    abstract public function getFile($configuration, $sourceFilePath);

    /**
     * Copies a source file or URL to a target path in the storage.
     *
     * The source must be an allowed file (see `Typo3PathResolver`) or a URL
     * of an allowed host. URLs are loaded with the resource loader of the
     * core (see `copyUrl()`). The target file name must have an allowed file
     * type.
     *
     * @throws \CPSIT\ImportExportCore\Exception\InvalidArgumentException if the source file or the target file type is not allowed
     * @throws MissingResourceException if the source URL is not allowed
     */
    protected function copyFile(string $sourceFilePath, string $targetPath): bool
    {
        $absoluteTargetPath = $this->getAbsoluteTargetPath($targetPath);
        if ($absoluteTargetPath === '') {
            return false;
        }
        if (ResourceAccess::isUrl($sourceFilePath)) {
            return $this->copyUrl($sourceFilePath, $absoluteTargetPath);
        }

        return @copy($this->resolveSourceFile($sourceFilePath), $absoluteTargetPath);
    }

    /**
     * Writes the contents of a URL to a file.
     *
     * The URL is loaded with the HTTP resource loader of the core and the HTTP
     * client of TYPO3: redirects are followed by the loader and checked like
     * the URL (scheme, allowed host). Returns false if the URL cannot be
     * loaded or the file cannot be written; an incomplete file is removed.
     *
     * @throws MissingResourceException if the URL is not an http(s) URL or the host of the URL or of a redirect is not allowed
     */
    protected function copyUrl(string $url, string $absoluteTargetPath): bool
    {
        $this->getResourceAccess()->assertUrlAllowed($url);
        try {
            $stream = $this->getResourceAccess()->openUrl($url);
        } catch (MissingResourceException $exception) {
            if ($exception->getCode() === HttpResourceLoader::ERROR_HOST_NOT_ALLOWED) {
                throw $exception;
            }

            return false;
        }

        try {
            $handle = @fopen($absoluteTargetPath, 'wb');
            if ($handle === false) {
                return false;
            }
            try {
                while (!$stream->eof()) {
                    $chunk = $stream->read(8192);
                    if (fwrite($handle, $chunk) !== \strlen($chunk)) {
                        throw new \RuntimeException('The file cannot be written.', 1_795_300_101);
                    }
                }
            } catch (\RuntimeException) {
                fclose($handle);
                @unlink($absoluteTargetPath);

                return false;
            }

            return fclose($handle);
        } finally {
            $stream->close();
        }
    }

    /**
     * @return string absolute path of an allowed file
     * @throws \CPSIT\ImportExportCore\Exception\InvalidArgumentException if the file is not allowed
     */
    protected function resolveSourceFile(string $sourceFilePath): string
    {
        return $this->getResourceAccess()->resolveFile($sourceFilePath);
    }

    /**
     * @return string absolute path in the storage, '' if it cannot be resolved
     */
    protected function getAbsoluteTargetPath(string $targetPath): string
    {
        $absolutePath = GeneralUtility::getFileAbsFileName($targetPath);
        if ($absolutePath !== '') {
            $this->getResourceAccess()->assertFileTypeAllowed($absolutePath);
        }

        return $absolutePath;
    }

    /**
     * Process record
     * Generates one or multiple file objects, adds them to the repository and the record field
     *
     * @param array $configuration
     * @param array $record
     * @return bool
     */
    public function process(array $configuration, array &$record): bool
    {
        $separator = ',';
        if (isset($configuration['separator'])) {
            $separator = $configuration['separator'];
        }
        $sourceField = $configuration['sourceField'];
        $targetField = $configuration['targetField'];

        $filePaths = GeneralUtility::trimExplode($separator, $record[$sourceField], true);

        // Prefix all files with the source path
        if (isset($configuration['sourcePath'])) {
            $filePaths = preg_filter('/^/', (string)$configuration['sourcePath'], $filePaths);
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

        $record[$targetField] = $fieldValue;

        return true;
    }

    /**
     * Check configuration
     */
    public function isConfigurationValid(array $configuration): bool
    {
        if (empty($configuration)) {
            $this->logError(1_499_007_587);
            return false;
        }
        if (!isset($configuration['targetDirectoryPath'])) {
            $this->logError(1_497_427_320);
            return false;
        }

        if (!isset($configuration['sourceField'])) {
            $this->logError(1_497_427_335);
            return false;
        }
        if (!isset($configuration['targetField'])) {
            $this->logError(1_497_427_336);
            return false;
        }

        if (!isset($configuration['storageId'])) {
            $this->logError(1_497_427_302);
            return false;
        }

        $this->initializeStorage($configuration);

        if (!$this->resourceStorage instanceof ResourceStorage) {
            $this->logError(1_497_427_346, [$configuration['storageId']]);
            return false;
        }

        if (!$this->resourceStorage->hasFolder($configuration['targetDirectoryPath'])) {
            $storageConfiguration = $this->resourceStorage->getConfiguration();
            $this->logError(1_497_427_363, [$storageConfiguration['basePath'] . ltrim((string)$configuration['targetDirectoryPath'], '/\\')]);

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
