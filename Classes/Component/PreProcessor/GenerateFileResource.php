<?php

declare(strict_types=1);

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

use CPSIT\ImportExportCore\Component\PreProcessor\PreProcessorInterface;
use CPSIT\ImportExportCore\LoggingInterface;
use CPSIT\ImportExportCore\Messaging\MessageContainer;
use CPSIT\T3importExport\Factory\FilePathFactory;
use CPSIT\T3importExport\Resource\FileIndexRepositoryTrait;
use CPSIT\T3importExport\Resource\ResourceTrait;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\Index\FileIndexRepository;
use TYPO3\CMS\Core\Utility\PathUtility;

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
class GenerateFileResource extends AbstractPreProcessor implements PreProcessorInterface, LoggingInterface
{
    use FileIndexRepositoryTrait;
    use GenerateFileTrait;
    use ResourceTrait;

    public function __construct(
        protected FileIndexRepository $fileIndexRepository,
        protected FilePathFactory $filePathFactory,
        /**
         * @todo temporarily disabled, because of dependency injection issues
         */
        //protected MessageContainer $messageContainer
    ) {}

    /**
     * Get File object
     *
     * @param array $configuration
     * @param string $sourceFilePath
     * @return FileInterface|null
     */
    public function getFile($configuration, $sourceFilePath)
    {
        $filePath = PathUtility::sanitizeTrailingSeparator($configuration['targetDirectoryPath'], DIRECTORY_SEPARATOR) . PathUtility::basename($sourceFilePath);

        if ($this->resourceStorage->hasFile($filePath)
        ) {
            return $this->resourceStorage->getFile($filePath);
        }

        $targetPath = $this->getTargetPath($configuration, $sourceFilePath);

        if (!@copy($sourceFilePath, $this->getAbsoluteFilePath($targetPath))) {
            return null;
        }

        /** @var FileInterface|File $fileObject */
        $fileObject = $this->resourceStorage->getFile($filePath);
        $this->fileIndexRepository->add($fileObject);

        return $fileObject;
    }
}
