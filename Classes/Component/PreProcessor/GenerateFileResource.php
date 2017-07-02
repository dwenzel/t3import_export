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

use CPSIT\T3importExport\FileIndexRepositoryTrait;
use CPSIT\T3importExport\ResourceStorageTrait;
use TYPO3\CMS\Core\Resource\ResourceStorage;
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
 */
class GenerateFileResource extends AbstractPreProcessor implements PreProcessorInterface
{
    use FileIndexRepositoryTrait, ResourceStorageTrait;

    /**
     * Errors by id
     *
     * @var array
     */
    static protected $errors = [
        1499007587 => ['Empty configuration', 'Configuration must not be empty'],
        1497427302 => ['Missing storage id', 'config[\'storageId\'] must be set'],
        1497427320 => ['Missing target directory ', 'config[\'targetDirectoryPath\` must be set'],
        1497427335 => ['Missing field name', 'config[\'fieldName\'] must be set'],
        1497427346 => ['Invalid storage', 'Could not find storage with id %s given in $config[\'storageId\']'],
        1497427363 => ['Missing directory', 'Directory %s given in $config[\'basePath\'] and $config[\'targetDirectory\'] does not exist.']
    ];

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
                $singleValue = $this->getFileObject($configuration, $filePath);
                $fieldValue[] = $singleValue;
            }

        } else {
            $fieldValue = $this->getFileObject($configuration, $filePaths[0]);
        }

        $record[$configuration['fieldName']] = $fieldValue;

        return true;
    }

    /**
     * Get File object
     *
     * @param $configuration
     * @param $file
     * @return \TYPO3\CMS\Core\Resource\File|string|null
     */
    public function getFileObject($configuration, $file)
    {
        $pathParts = pathinfo($file);
        $filePath = $configuration['targetDirectoryPath'] . $pathParts['basename'];

        if ($this->resourceStorage->hasFile($filePath)
        ) {
            return $this->resourceStorage->getFile($filePath);
        }

        $storageConfiguration = $this->resourceStorage->getConfiguration();

        $targetDirectoryPath = rtrim(GeneralUtility::getFileAbsFileName($storageConfiguration['basePath']),
                '/') . $configuration['targetDirectoryPath'];

        // @todo allow reading remote resource too!
        if (@copy($file, $targetDirectoryPath . $pathParts['basename'])) {
            // @todo add error message on failure
            /** @var \TYPO3\CMS\Core\Resource\File $fileObject */
            $fileObject = $this->resourceStorage->getFile($filePath);
            $this->fileIndexRepository->add($fileObject);

            return $fileObject;
        }

        return null;
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


}
