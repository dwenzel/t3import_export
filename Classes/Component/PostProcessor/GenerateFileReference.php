<?php

namespace CPSIT\T3importExport\Component\PostProcessor;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017 Dirk Wenzel <wenzel@cps-it.de>
 *  All rights reserved
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * A copy is found in the text file GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use CPSIT\T3importExport\Resource\FileIndexRepositoryTrait;
use CPSIT\T3importExport\Resource\FileReferenceFactoryTrait;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

/**
 * Class GenerateFileReference
 */
class GenerateFileReference extends AbstractPostProcessor
    implements PostProcessorInterface
{
    use FileReferenceFactoryTrait, FileIndexRepositoryTrait;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
     */
    protected $persistenceManager;

    /**
     * Inject persistenceManager
     *
     * @param \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface $persistenceManager
     */
    public function injectPersistenceManager(PersistenceManagerInterface $persistenceManager)
    {
        $this->persistenceManager = $persistenceManager;
    }

    /**
     * processes the converted record
     *
     * @param array $configuration
     * @param mixed $convertedRecord
     * @param array $record
     * @return bool
     */
    public function process($configuration, &$convertedRecord, &$record)
    {
        $fieldName = $configuration['targetField'];
        $fileId = $record[$configuration['sourceField']];

        if (
            !ObjectAccess::isPropertySettable($convertedRecord, $fieldName)
            || !MathUtility::canBeInterpretedAsInteger($fileId)
        ) {
            return false;
        }

        $fileId = (int)$fileId;

        if (ObjectAccess::isPropertyGettable($convertedRecord, $fieldName)) {
            $targetFieldValue = ObjectAccess::getProperty($convertedRecord, $fieldName);

            if ($targetFieldValue instanceof FileReference) {
                $existingFileId = $targetFieldValue->getOriginalResource()
                    ->getOriginalFile()->getUid();

                if ( $existingFileId === $fileId) {
                    // field references same file - nothing to do
                    return false;
                } else {
                    // remove existing reference if not equal file
                    $this->persistenceManager->remove($targetFieldValue);
                }
            }
        }

        if ($this->fileIndexRepository->findOneByUid($fileId) === false) {
            return false;
        }

        $fileReference = $this->fileReferenceFactory->create($fileId, $configuration);

        ObjectAccess::setProperty($convertedRecord, $fieldName, $fileReference);

        return true;
    }

    /**
     * Tells whether the configuration is valid
     *
     * @param array $configuration
     * @return bool
     */
    public function isConfigurationValid(array $configuration)
    {
        if (
            empty($configuration['sourceField'])
            || !is_string($configuration['sourceField'])
        ) {
            return false;
        }
        if (
            empty($configuration['targetField'])
            || !is_string($configuration['targetField'])
        ) {
            return false;
        }
        if (!empty($configuration['targetPage'])
            && !MathUtility::canBeInterpretedAsInteger($configuration['targetPage'])
        ) {
            return false;
        }

        return true;
    }
}
