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

use CPSIT\T3importExport\LoggingInterface;
use CPSIT\T3importExport\LoggingTrait;
use CPSIT\T3importExport\Messaging\MessageContainer;
use CPSIT\T3importExport\Persistence\Factory\FileReferenceFactory;
use CPSIT\T3importExport\Resource\FileIndexRepositoryTrait;
use TYPO3\CMS\Core\Resource\Index\FileIndexRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\Reflection\Exception\PropertyNotAccessibleException;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

/**
 * Class GenerateFileReference
 */
class GenerateFileReference extends AbstractPostProcessor
    implements PostProcessorInterface, LoggingInterface
{
    use FileIndexRepositoryTrait, LoggingTrait;

    /**
     * Error by id
     * <unique id> => ['title', ['message']
     */
    final public const ERROR_CODES = [
        1_510_524_677 => ['Missing source field', 'config[\'sourceField\'] must be set'],
        1_510_524_678 => ['Missing target field', 'config[\'targetField\'] must be set'],
        1_510_524_679 => ['Invalid target page', 'Given value %s for config[\'targetPage\'] could not be interpreted as integer'],
    ];

    protected PersistenceManagerInterface $persistenceManager;

    protected FileReferenceFactory $fileReferenceFactory;

    public function __construct(
        PersistenceManagerInterface $persistenceManager = null,
        FileReferenceFactory $fileReferenceFactory = null,
        FileIndexRepository $fileIndexRepository = null,
        MessageContainer $messageContainer = null)
    {
        if ($persistenceManager === null) {
            /** @var PersistenceManagerInterface $persistenceManager */
            $persistenceManager = (GeneralUtility::makeInstance(ObjectManager::class))
                ->get(PersistenceManagerInterface::class);
        }
        if ($persistenceManager !== null) {
            $this->persistenceManager = $persistenceManager;
        }
        $this->fileReferenceFactory = $fileReferenceFactory ?? GeneralUtility::makeInstance(
                FileReferenceFactory::class
            );
        $this->fileIndexRepository = $fileIndexRepository ?? GeneralUtility::makeInstance(
                FileIndexRepository::class
            );
        $this->messageContainer = $messageContainer ?? GeneralUtility::makeInstance(
                MessageContainer::class
            );
    }

    /**
     * processes the converted record
     *
     * @param array $configuration
     * @param mixed $convertedRecord
     * @param array $record
     * @return bool
     * @throws PropertyNotAccessibleException
     */
    public function process(array $configuration, &$convertedRecord, array &$record): bool
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

                if ($existingFileId === $fileId) {
                    // field references same file - nothing to do
                    return false;
                }

                // remove existing reference if not equal file
                $this->persistenceManager->remove($targetFieldValue);
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
    public function isConfigurationValid(array $configuration): bool
    {
        if (
            empty($configuration['sourceField'])
            || !is_string($configuration['sourceField'])
        ) {
            $this->logError(1_510_524_677);
            return false;
        }
        if (
            empty($configuration['targetField'])
            || !is_string($configuration['targetField'])
        ) {
            $this->logError(1_510_524_678);
            return false;
        }
        if (!empty($configuration['targetPage'])
            && !MathUtility::canBeInterpretedAsInteger($configuration['targetPage'])
        ) {
            $this->logError(1_510_524_679, (string)$configuration['targetPage']);
            return false;
        }

        return true;
    }
}
