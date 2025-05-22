<?php

declare(strict_types=1);

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

use CPSIT\ImportExportCore\LoggingInterface;
use CPSIT\ImportExportCore\LoggingTrait;
use CPSIT\ImportExportCore\Messaging\MessageContainer;
use CPSIT\ImportExportCore\Messaging\MessageContainerInterface;
use CPSIT\T3importExport\Persistence\Factory\FileReferenceFactory;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Index\FileIndexRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\Reflection\Exception\PropertyNotAccessibleException;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use CPSIT\ImportExportCore\Component\PostProcessor\PostProcessorInterface;

/**
 * Class GenerateFileReference
 */
class GenerateFileReference extends AbstractPostProcessor implements PostProcessorInterface, LoggingInterface
{
    use LoggingTrait;

    public const string TABLE_SYS_FILE_REFERENCE = 'sys_file_reference';

    /**
     * Error by id
     * <unique id> => ['title', ['message']
     */
    final public const array ERROR_CODES = [
        1_510_524_677 => ['Missing source field', 'config[\'sourceField\'] must be set'],
        1_510_524_678 => ['Missing target field', 'config[\'targetField\'] must be set'],
        1_510_524_679 => ['Invalid target page', 'Given value %s for config[\'targetPage\'] could not be interpreted as integer'],
    ];

    public function __construct(
        protected PersistenceManagerInterface $persistenceManager,
        protected FileReferenceFactory $fileReferenceFactory,
        protected FileIndexRepository $fileIndexRepository,
        /**
         * @todo temporarily disabled, because of dependency injection issues
         */
        //protected MessageContainer $messageContainer
    ) {}

    /**
     * processes the converted record
     *
     * @param array $configuration
     * @param mixed $convertedRecord
     * @param array $record
     * @return bool
     * @throws PropertyNotAccessibleException
     */
    public function process(array $configuration, mixed &$convertedRecord, array &$record): bool
    {
        $sourceField = $configuration['sourceField'];
        $targetField = $configuration['targetField'];
        $identityField = $configuration['identityField'] ?? '__identity';
        $tableName = $configuration['tableName'] ?? '';

        $fileId = isset($record[$sourceField]) ? $record[$sourceField] : null;

        if (is_object($convertedRecord)
            && (!ObjectAccess::isPropertySettable($convertedRecord, $targetField)
            || !MathUtility::canBeInterpretedAsInteger($fileId))
        ) {
            return false;
        }
        if ($fileId instanceof File) {
            $fileId = $fileId->getUid();
        }
        $fileId = (int)$fileId;

        if ($this->fileIndexRepository->findOneByUid($fileId) === false) {
            return false;
        }

        $foreignUid = null;
        if (is_array($convertedRecord) && isset($convertedRecord[$identityField])) {
            $foreignUid = (int)$convertedRecord[$identityField];
        } elseif (is_object($convertedRecord) && isset($convertedRecord->$identityField)) {
            $foreignUid = (int)$convertedRecord->$identityField;
        }

        // Only check if file reference exists if we have a foreign UID
        if ($foreignUid !== null && $this->fileReferenceExists($tableName, $fileId, $foreignUid, $targetField)) {
            return false;
        }

        if (
            is_object($convertedRecord)
            && ObjectAccess::isPropertyGettable($convertedRecord, $targetField)) {
            $targetFieldValue = ObjectAccess::getProperty($convertedRecord, $targetField);

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

            $fileReference = $this->fileReferenceFactory->createFileReferenceObject($fileId, $configuration, $foreignUid);
            ObjectAccess::setProperty($convertedRecord, $targetField, $fileReference);
        }

        if (!is_object($convertedRecord)
        ) {
            $this->createFileReferenceRecord($configuration, $fileId, $foreignUid);
            $convertedRecord[$targetField] = 1;
        }

        return true;
    }

    /**
     * Tells whether the configuration is valid
     */
    #[\Override]
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
            $this->logError(1_510_524_679, [(string)$configuration['targetPage']]);
            return false;
        }

        return true;
    }

    protected function createFileReferenceRecord(array $configuration, int $fileId, int $foreignUid)
    {
        $row = [
            'uid_local' => $fileId,
            'uid_foreign' => $foreignUid,
            'tablenames' => $configuration['tableName'] ?? '',
            'fieldname' => $configuration['fieldName'] ?? '',
            'crop' => $configuration['crop'] ?? '',
            'pid' => $configuration['targetPage'] ?? 0,
        ];
        $connection = (GeneralUtility::makeInstance(ConnectionPool::class))
            ->getConnectionForTable(self::TABLE_SYS_FILE_REFERENCE);
        $result = $connection->insert(self::TABLE_SYS_FILE_REFERENCE, $row);
    }
    protected function fileReferenceExists(
        string $tableName,
        int $localUid,
        int $foreignUid,
        string $fieldName = ''
    ): bool {
        $connection = (GeneralUtility::makeInstance(ConnectionPool::class))->getConnectionForTable(
            self::TABLE_SYS_FILE_REFERENCE,
        );
        $queryBuilder = $connection->createQueryBuilder();
        $result = $queryBuilder->count('*')
            ->from(self::TABLE_SYS_FILE_REFERENCE)
            ->where(
                $queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq(
                        'uid_foreign',
                        $queryBuilder->createNamedParameter($foreignUid, Connection::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'uid_local',
                        $queryBuilder->createNamedParameter($localUid, Connection::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'tablenames',
                        $queryBuilder->createNamedParameter($tableName, Connection::PARAM_STR)
                    ),
                    $queryBuilder->expr()->eq(
                        'fieldname',
                        $queryBuilder->createNamedParameter($fieldName, Connection::PARAM_STR)
                    )
                )
            )
            ->executeQuery()->fetchOne();
        return $result === 1;
    }
}
