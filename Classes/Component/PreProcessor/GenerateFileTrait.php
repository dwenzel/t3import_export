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
use CPSIT\ImportExportCore\LoggingTrait;
use CPSIT\T3importExport\Factory\FilePathFactory;
use CPSIT\T3importExport\Resource\FileSourceGuard;
use CPSIT\T3importExport\Resource\FileTargetGuardTrait;
use CPSIT\T3importExport\Resource\ResourceStorageTrait;
use CPSIT\T3importExport\Resource\UrlSourceLoader;
use Psr\Http\Message\StreamInterface;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\Security\SvgSanitizer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Trait GenerateFileTrait
 */
trait GenerateFileTrait
{
    use FileTargetGuardTrait;
    use ResourceStorageTrait;
    use LoggingTrait;

    protected FilePathFactory $filePathFactory;

    protected ?FileSourceGuard $fileSourceGuard = null;

    protected ?UrlSourceLoader $urlSourceLoader = null;

    public function setFileSourceGuard(FileSourceGuard $fileSourceGuard): void
    {
        $this->fileSourceGuard = $fileSourceGuard;
    }

    protected function getFileSourceGuard(): FileSourceGuard
    {
        return $this->fileSourceGuard ??= new FileSourceGuard();
    }

    public function setUrlSourceLoader(UrlSourceLoader $urlSourceLoader): void
    {
        $this->urlSourceLoader = $urlSourceLoader;
    }

    protected function getUrlSourceLoader(): UrlSourceLoader
    {
        return $this->urlSourceLoader ??= new UrlSourceLoader(
            GeneralUtility::makeInstance(RequestFactory::class),
            $this->getFileSourceGuard()
        );
    }

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
     * Copies a source file or URL to a target file.
     *
     * Local files must be in an allowed directory, URLs must be http(s) URLs
     * of an allowed host (see `FileSourceGuard`). The content is written to a
     * temporary file in the target directory, which then replaces the target
     * file. SVG files are sanitized like FAL does. Returns false if the source
     * cannot be read or the target cannot be written.
     *
     * @throws InvalidArgumentException if the source is not allowed
     */
    protected function copySourceFile(string $sourceFilePath, string $absoluteTargetPath): bool
    {
        if ($absoluteTargetPath === '') {
            return false;
        }
        if (FileSourceGuard::isUrl($sourceFilePath)) {
            $this->getFileSourceGuard()->assertUrlAllowed($sourceFilePath);
            $stream = $this->getUrlSourceLoader()->open($sourceFilePath);
            if ($stream === null) {
                return false;
            }
        } else {
            $path = $this->getFileSourceGuard()->resolveFile($sourceFilePath);
            if (!is_file($path)) {
                return false;
            }
            $source = @fopen($path, 'rb');
            if ($source === false) {
                return false;
            }
            $stream = new Stream($source);
        }

        $temporaryPath = \dirname($absoluteTargetPath) . '/.' . basename($absoluteTargetPath) . '.' . bin2hex(random_bytes(8)) . '.part';
        try {
            $handle = @fopen($temporaryPath, 'xb');
            if ($handle === false) {
                return false;
            }
            $written = $this->writeStream($stream, $handle);
            if (!fclose($handle) || !$written) {
                @unlink($temporaryPath);

                return false;
            }
            if (strtolower(pathinfo($absoluteTargetPath, PATHINFO_EXTENSION)) === 'svg') {
                GeneralUtility::makeInstance(SvgSanitizer::class)->sanitizeFile($temporaryPath);
            }
            if (!@rename($temporaryPath, $absoluteTargetPath)) {
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
                fn(string $filePath): string => $this->getFileSourceGuard()->buildLocation($sourcePath, $filePath),
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
     * The file name of the target is the one of the source. It must not
     * match the fileDenyPattern of TYPO3 (the file is copied without FAL).
     *
     * @param array $configuration
     * @param string $sourcePath
     * @return string
     * @throws InvalidArgumentException if the file name is not allowed
     */
    protected function getTargetPath($configuration, $sourcePath)
    {
        $this->getFileTargetGuard()->assertFileName(PathUtility::basename((string)$sourcePath));
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
