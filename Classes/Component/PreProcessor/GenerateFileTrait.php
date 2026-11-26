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
use CPSIT\ImportExportCore\Exception\InvalidArgumentException;
use CPSIT\ImportExportCore\Exception\MissingResourceException;
use CPSIT\ImportExportCore\LoggingTrait;
use CPSIT\ImportExportCore\Messaging\Message;
use CPSIT\ImportExportCore\Resource\HttpResourceLoader;
use CPSIT\T3importExport\Factory\FilePathFactory;
use CPSIT\T3importExport\Resource\ResourceAccess;
use CPSIT\T3importExport\Resource\ResourceStorageTrait;
use CPSIT\T3importExport\Resource\ResourceTrait;
use Psr\Http\Message\StreamInterface;
use TYPO3\CMS\Core\Http\Stream;
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

    /**
     * Message ID of the warning that a URL cannot be loaded
     */
    public const int WARNING_URL_NOT_LOADED = 1_795_300_501;

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
     * of an allowed host. An `EXT:` path is only allowed below an `EXT:`
     * `sourcePath` of the configuration. URLs are loaded with the resource
     * loader of the core (see `copyUrl()`). The target file name must be
     * allowed (see `getAbsoluteTargetPath()`). The content is written to a
     * temporary file in the target directory, which then replaces the target
     * file (see `writeTargetFile()`).
     *
     * @param string|null $sourcePath the `sourcePath` of the configuration
     * @throws InvalidArgumentException if the source file or the target file type is not allowed
     * @throws MissingResourceException if the source URL is not allowed
     */
    protected function copyFile(string $sourceFilePath, string $targetPath, ?string $sourcePath = null): bool
    {
        $absoluteTargetPath = $this->getAbsoluteTargetPath($targetPath);
        if ($absoluteTargetPath === '') {
            return false;
        }
        if (ResourceAccess::isUrl($sourceFilePath)) {
            return $this->copyUrl($sourceFilePath, $absoluteTargetPath);
        }

        $absoluteSourcePath = $this->resolveSourceFile($sourceFilePath, $sourcePath);
        if (!is_file($absoluteSourcePath)) {
            return false;
        }
        $source = @fopen($absoluteSourcePath, 'rb');
        if ($source === false) {
            return false;
        }

        return $this->writeTargetFile(new Stream($source), $absoluteTargetPath);
    }

    /**
     * Writes the contents of a URL to a file.
     *
     * The URL is loaded with the HTTP resource loader of the core and the HTTP
     * client of TYPO3: redirects are followed by the loader and checked like
     * the URL (scheme, allowed host). The content is written to a temporary
     * file in the target directory, which then replaces the target file.
     * Returns false if the URL cannot be loaded (logged as warning) or the
     * file cannot be written; the target file is not changed then.
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
            $this->logMessage(
                'URL not loaded',
                $exception->getMessage(),
                Message::SEVERITY_WARNING,
                self::WARNING_URL_NOT_LOADED
            );

            return false;
        }

        return $this->writeTargetFile($stream, $absoluteTargetPath);
    }

    /**
     * Writes a stream to a temporary file in the target directory
     * (`.<name>.<random>.part`), which then replaces the target file. Returns
     * false if the stream cannot be read or the file cannot be written; the
     * target file is not changed then. The stream is closed.
     */
    protected function writeTargetFile(StreamInterface $stream, string $absoluteTargetPath): bool
    {
        $temporaryPath = \dirname($absoluteTargetPath) . '/.' . basename($absoluteTargetPath) . '.' . bin2hex(random_bytes(8)) . '.part';
        try {
            $handle = @fopen($temporaryPath, 'xb');
            if ($handle === false) {
                return false;
            }
            $written = $this->writeStream($stream, $handle);
            if (!fclose($handle) || !$written || !@rename($temporaryPath, $absoluteTargetPath)) {
                @unlink($temporaryPath);

                return false;
            }

            return true;
        } finally {
            $stream->close();
        }
    }

    /**
     * @param resource $handle
     * @return bool false if the stream cannot be read or the file cannot be written
     */
    private function writeStream(StreamInterface $stream, $handle): bool
    {
        try {
            while (!$stream->eof()) {
                $chunk = $stream->read(8192);
                if (fwrite($handle, $chunk) !== \strlen($chunk)) {
                    return false;
                }
            }
        } catch (\RuntimeException) {
            return false;
        }

        return true;
    }

    /**
     * @param string|null $sourcePath the `sourcePath` of the configuration
     * @return string absolute path of an allowed file
     * @throws InvalidArgumentException if the file is not allowed
     */
    protected function resolveSourceFile(string $sourceFilePath, ?string $sourcePath = null): string
    {
        return $this->getResourceAccess()->resolveSourceFile($sourceFilePath, $sourcePath);
    }

    /**
     * @param array<string, mixed> $configuration
     */
    protected function getSourcePath(array $configuration): ?string
    {
        return isset($configuration['sourcePath']) ? (string)$configuration['sourcePath'] : null;
    }

    /**
     * The file name must be allowed for written files (see
     * `Typo3PathResolver::assertTargetFileName()`).
     *
     * @return string absolute path in the storage, '' if it cannot be resolved
     * @throws InvalidArgumentException if the file name is not allowed
     */
    protected function getAbsoluteTargetPath(string $targetPath): string
    {
        $absolutePath = GeneralUtility::getFileAbsFileName($targetPath);
        if ($absolutePath !== '') {
            $this->getResourceAccess()->assertTargetFileAllowed($absolutePath);
        }

        return $absolutePath;
    }

    /**
     * File name of a source file; for URLs the last segment of the path,
     * without query and fragment
     */
    protected function getSourceFileName(string $sourceFilePath): string
    {
        if (ResourceAccess::isUrl($sourceFilePath)) {
            $sourceFilePath = (string)parse_url($sourceFilePath, PHP_URL_PATH);
        }

        return PathUtility::basename($sourceFilePath);
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
            $sourcePath = (string)$configuration['sourcePath'];
            $filePaths = array_map(
                fn(string $filePath): string => $this->buildSourceLocation($sourcePath, $filePath),
                $filePaths
            );
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
     * Joins the source path of the configuration and a value of the record.
     * If the source path is a URL, the value cannot change its scheme, host,
     * port or user information.
     *
     * @throws MissingResourceException if the value changes the scheme, host or port of a URL source path
     */
    protected function buildSourceLocation(string $sourcePath, string $value): string
    {
        $location = $sourcePath . $value;
        if (!ResourceAccess::isUrl($sourcePath)) {
            return $location;
        }
        $base = parse_url($sourcePath);
        $result = parse_url($location);
        foreach (['scheme', 'host', 'port', 'user', 'pass'] as $component) {
            if (
                !\is_array($base) || !\is_array($result)
                || strtolower((string)($base[$component] ?? '')) !== strtolower((string)($result[$component] ?? ''))
            ) {
                throw new MissingResourceException(
                    \sprintf('Resource "%s" cannot be loaded: the value must not change the scheme, host or port of the source path.', $location),
                    HttpResourceLoader::ERROR_HOST_NOT_ALLOWED
                );
            }
        }

        return $location;
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
     * The file name of the target is the one of the source (for URLs the last
     * segment of the path).
     *
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

        return $targetDirectoryPath . $this->getSourceFileName((string)$sourcePath);
    }
}
