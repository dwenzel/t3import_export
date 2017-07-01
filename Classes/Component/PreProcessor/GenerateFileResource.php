<?php

namespace CPSIT\T3importExport\Component\PreProcessor;

/**
 * Copyright notice
 * (c) 2016. Vladimir Falcón Piva <falcon@cps-it.de>
 * All rights reserved
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * A copy is found in the text file GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * This copyright notice MUST APPEAR in all copies of the script!
 */

use TYPO3\CMS\Core\Resource\Index\FileIndexRepository;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class GenerateFileResource
 *
 * Generates a file object (\TYPO3\CMS\Core\Resource\File)
 * by reading a given file by name and optional source path.
 * The source path can be an external URL too.
 *
 * We check whether it already exist inside the storage (given by storageId).
 * If not this pre processor copies the file to the storage
 * and adds the file object to the file index repository.
 * If it exist we use the according file object.
 *
 * Finally the file object is added to the target field of
 * the record. Fields with single file references and object
 * storage of file references are handled.
 *
 * @package CPSIT\AhkImport\Component\PreProcessor
 */
class GenerateFileResource extends AbstractPreProcessor implements PreProcessorInterface
{
    /**
     * @var \TYPO3\CMS\Core\Resource\ResourceFactory
     */
    protected $resourceFactory;

    /**
     * @var \TYPO3\CMS\Core\Resource\Index\FileIndexRepository
     */
    protected $fileIndexRepository;

    /**
     * @var \TYPO3\CMS\Core\Resource\ResourceStorage
     */
    protected $storage;

    /**
     * @var \TYPO3\CMS\Core\Resource\StorageRepository
     */
    protected $storageRepository;

    /**
     * Errors by id
     *
     * @var array
     */
    static protected $errors = [
        1497427302 => ['Missing storage id', 'config[\'storageId\'] must be set'],
        1497427320 => ['Missing target directory ', 'config[\'targetDirectoryPath\` must be set'],
        1497427335 => ['Missing field name', 'config[\'fieldName\'] must be set'],
        1497427346 => ['Invalid storage', 'Could not find storage with id %s given in $config[\'storageId\']'],
        1497427363 => ['Missing directory', 'Directory %s given in $config[\'basePath\'] and $config[\'targetDirectory\'] does not exist.']
    ];

    /**
     * Injects the storage repository
     *
     * @param StorageRepository $storageRepository
     */
    public function injectStorageRepository(StorageRepository $storageRepository)
    {
        $this->storageRepository = $storageRepository;
    }

    /**
     * Injects the resource factory
     *
     * @param ResourceFactory $resourceFactory
     */
    public function injectResourceFactory(ResourceFactory $resourceFactory)
    {
        $this->resourceFactory = $resourceFactory;
    }

    /**
     * Injects the file index repository
     *
     * @param FileIndexRepository $fileIndexRepository
     */
    public function injectFileIndexRepository(FileIndexRepository $fileIndexRepository)
    {
        $this->fileIndexRepository = $fileIndexRepository;
    }

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
        $fileObjects = [];
        if ($configuration['multipleRows']) {
            foreach ($filePaths as $filePath) {
                $fileObjects[] = $this->generateFileObject($configuration, $filePath);
            }
            $record[$configuration['fieldName']] = $fileObjects;
        } else {
            $record[$configuration['fieldName']] = $this->generateFileObject($configuration, $filePaths[0]);
        }

        return true;
    }

    /**
     * Get File object
     *
     * @param $configuration
     * @param $file
     * @return \TYPO3\CMS\Core\Resource\File|string|null
     */
    public function generateFileObject($configuration, $file)
    {
        $pathParts = pathinfo($file);

        if ((!isset($configuration['fieldType']) || $configuration['fieldType'] !== 'string')
            && $this->storage->hasFile($configuration['targetDirectoryPath'] . $pathParts['basename'])
        ) {
            return $this->storage->getFile($configuration['targetDirectoryPath'] . $pathParts['basename']);
        }

        $storageConfiguration = $this->storage->getConfiguration();

        $targetDirectoryPath = rtrim(GeneralUtility::getFileAbsFileName($storageConfiguration['basePath']),
                '/') . $configuration['targetDirectoryPath'];

        if (@copy($file, $targetDirectoryPath . $pathParts['basename'])) {
            if (isset($configuration['fieldType']) && $configuration['fieldType'] == 'string') {
                $fileObject = $storageConfiguration['basePath'] . ltrim($configuration['targetDirectoryPath'] . $pathParts['basename'], '/\\');
            } else {
                /** @var \TYPO3\CMS\Core\Resource\File $fileObject */
                $fileObject = $this->storage->getFile($configuration['targetDirectoryPath'] . $pathParts['basename']);
                $this->fileIndexRepository->add($fileObject);
            }

            return $fileObject;
        }

        return null;
    }

    /**
     * Initializes the resource storage
     *
     * @param array $configuration
     */
    public function initializeStorage($configuration)
    {
        $this->storage = $this->storageRepository->findByUid($configuration['storageId']);
    }

    /**
     * Check configuration
     *
     * @param array $configuration
     * @return bool
     */
    public function isConfigurationValid(array $configuration)
    {
        if (!isset($configuration['storageId'])) {
            $this->logError(1497427302);
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

        if (!$this->storage instanceof ResourceStorage) {
            $this->logError(1497427346, [$configuration['storageId']]);
            return false;
        }

        if (!$this->storage->hasFolder($configuration['targetDirectoryPath'])) {
            $storageConfiguration = $this->storage->getConfiguration();
            $this->logError(1497427363, [$storageConfiguration['basePath'] . ltrim($configuration['targetDirectoryPath'], '/\\')]);

            return false;
        }

        return true;
    }

}
