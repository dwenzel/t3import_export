<?php

namespace CPSIT\T3importExport\Persistence;

use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Backend;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager as BasePersistenceManager;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

class PersistenceManager extends BasePersistenceManager implements PersistenceManagerInterface
{
    static protected $transaction = false;

    public function persistTransaction($object)
    {
        /** @var DomainObjectInterface $object */
        $sqlData = $this->collectQueryInformation($object);
        $id = $this->commitData($sqlData);
        if($id) {
            $object->_setProperty('uid', (int)$id);
        }

        return $object;
    }

    public function persistAll()
    {
        //$this->clearState();
        //parent::persistAll();
    }

    /**
     * @param DomainObjectInterface $object
     * @return array
     */
    protected function collectQueryInformation(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object)
    {
        $type = $this->findSqlType($object);
        $sqlData = [];
        $sqlData[$type][] = $this->mapObject($object);
        return $sqlData;
    }

    /**
     * @param DomainObjectInterface $object
     * @return array
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\UnexpectedTypeException
     */
    protected function mapObject(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object)
    {
        /** @var Backend $backend */
        $backend = $this->backend;
        $backend->getDataMapper();

        $dataMap = $backend->getDataMapper()->getDataMap(get_class($object));
        $row = [];
        $MM = [];
        $properties = $object->_getProperties();
        $mToOneParent = null;
        $mToOneChild = null;
        foreach ($properties as $propertyName => $propertyValue) {
            if (!$dataMap->isPersistableProperty($propertyName) || $this->propertyValueIsLazyLoaded($propertyValue)) {
                continue;
            }
            $columnMap = $dataMap->getColumnMap($propertyName);
            if ($columnMap->getTypeOfRelation() === ColumnMap::RELATION_HAS_ONE) {
                $row[$columnMap->getColumnName()] = 0;
                $mToOneParent = $propertyName;
            } elseif ($columnMap->getTypeOfRelation() !== ColumnMap::RELATION_NONE) {
                if ($columnMap->getParentKeyFieldName() === null) {
                    // CSV type relation
                    $row[$columnMap->getColumnName()] = '';
                } else {
                    // MM type relation
                    // TODO: add correct count value
                    $count = 0;
                    if ($propertyValue instanceof ObjectStorage) {
                        $propertyValue->rewind();

                        while ($propertyValue->valid()) {
                            $subObject = $propertyValue->current();
                            $type = $this->findSqlType($subObject);
                            $data = $this->mapObject($subObject);
                            if (isset($data['parent']) && $data['parent'] != null) {
                                $mToOneChild = $data['parent'];
                            }
                            $MM[$type][$columnMap->getColumnName()][] = $data;
                            $propertyValue->next();
                        }
                        $count += $propertyValue->count();
                    }

                    $row[$columnMap->getColumnName()] = $count;
                }
            } elseif ($propertyValue !== null) {
                $row[$columnMap->getColumnName()] = $backend->getDataMapper()->getPlainValue($propertyValue);
            }
        }
        $this->addCommonFieldsToRow($object, $row);
        if ($dataMap->getLanguageIdColumnName() !== null && $object->_getProperty('_languageUid') === null) {
            $row[$dataMap->getLanguageIdColumnName()] = 0;
            $object->_setProperty('_languageUid', 0);
        }
        if ($dataMap->getTranslationOriginColumnName() !== null) {
            $row[$dataMap->getTranslationOriginColumnName()] = 0;
        }
        if ($dataMap->getTranslationOriginDiffSourceName() !== null) {
            $row[$dataMap->getTranslationOriginDiffSourceName()] = '';
        }

        return array(
            'table' => $dataMap->getTableName(),
            'type' => get_class($object),
            'columns' => $row,
            'child' => $mToOneChild,
            'parent' => $mToOneParent,
            'mm' => $MM
        );
    }

    protected function propertyValueIsLazyLoaded($propertyValue)
    {
        if ($propertyValue instanceof \TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy) {
            return true;
        }
        if ($propertyValue instanceof \TYPO3\CMS\Extbase\Persistence\Generic\LazyObjectStorage) {
            if ($propertyValue->isInitialized() === false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Adds common databse fields to a row
     *
     * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object
     * @param array &$row
     * @return void
     */
    protected function addCommonFieldsToRow(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object, array &$row)
    {
        /** @var Backend $backend */
        $backend = $this->backend;
        $dataMap = $backend->getDataMapper()->getDataMap(get_class($object));
        $this->addCommonDateFieldsToRow($object, $row);
        if ($dataMap->getRecordTypeColumnName() !== null && $dataMap->getRecordType() !== null) {
            $row[$dataMap->getRecordTypeColumnName()] = $dataMap->getRecordType();
        }
        if ($object->_isNew() && !isset($row['pid'])) {
            $row['pid'] = $this->determineStoragePageIdForNewRecord($object);
        }
    }

    /**
     * Adjustes the common date fields of the given row to the current time
     *
     * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object
     * @param array &$row The row to be updated
     * @return void
     */
    protected function addCommonDateFieldsToRow(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object, array &$row)
    {
        /** @var Backend $backend */
        $backend = $this->backend;
        $dataMap = $backend->getDataMapper()->getDataMap(get_class($object));
        if ($object->_isNew() && $dataMap->getCreationDateColumnName() !== null) {
            $row[$dataMap->getCreationDateColumnName()] = $GLOBALS['EXEC_TIME'];
        }
        if ($dataMap->getModificationDateColumnName() !== null) {
            $row[$dataMap->getModificationDateColumnName()] = $GLOBALS['EXEC_TIME'];
        }
    }

    /**
     * Determine the storage page ID for a given NEW record
     *
     * This does the following:
     * - If the domain object has an accessible property 'pid' (i.e. through a getPid() method), that is used to store the record.
     * - If there is a TypoScript configuration "classes.CLASSNAME.newRecordStoragePid", that is used to store new records.
     * - If there is no such TypoScript configuration, it uses the first value of The "storagePid" taken for reading records.
     *
     * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object
     * @return int the storage Page ID where the object should be stored
     */
    protected function determineStoragePageIdForNewRecord(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object = null)
    {
        $objectManager =
            \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
        /** @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager */
        $configurationManager =
            $objectManager->get('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManagerInterface');

        $frameworkConfiguration = $configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
        if ($object !== null) {
            if (\TYPO3\CMS\Extbase\Reflection\ObjectAccess::isPropertyGettable($object, 'pid')) {
                $pid = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getProperty($object, 'pid');
                if (isset($pid)) {
                    return (int)$pid;
                }
            }
            $className = get_class($object);
            if (isset($frameworkConfiguration['persistence']['classes'][$className]) && !empty($frameworkConfiguration['persistence']['classes'][$className]['newRecordStoragePid'])) {
                return (int)$frameworkConfiguration['persistence']['classes'][$className]['newRecordStoragePid'];
            }
        }
        $storagePidList = \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $frameworkConfiguration['persistence']['storagePid']);
        return (int)$storagePidList[0];
    }

    protected function commitData($sqlData)
    {
        $inserts = $sqlData['insert'];
        $updates = $sqlData['update'];
        if ($inserts) {
            $insertQueryIds = $this->execInsertConcatenatedQueries($inserts);
        }
        if ($updates) {
            $updateQueryIds = $this->execUpdateConcatenatedQueries($updates);
        }

        //$query = $this->createQueryForType()
        return array_pop($insertQueryIds);
    }

    /**
     * @param array $sqlData
     * @param null|array $parentId
     * @return array
     */
    protected function execInsertConcatenatedQueries(array $sqlData, $parentId = null)
    {
        /** @var DatabaseConnection $db */
        $db =  $GLOBALS['TYPO3_DB'];

        $queries = [];
        $mm = [];
        foreach ($sqlData as $singleData) {
            if (!isset($queries[$singleData['type']])) {
                $queries[$singleData['type']] = array(
                    'table' => $singleData['table'],
                    'columns' => array_keys($singleData['columns']),
                    'parent' => $singleData['parent'],
                    'child' => $singleData['child'],
                    'values' => [],
                );
            }

            if (isset($parentId)) {
                foreach ($parentId as $col => $pid) {
                    if (isset($singleData['columns'][$col])) {
                        $singleData['columns'][$col] = $pid;
                    }

                }
            }

            $queries[$singleData['type']]['values'][] = array_values($singleData['columns']);
            $mm[] = $singleData['mm'];
        }

        // TODO: add sub mm call for updates
        $insertIds = [];
        foreach ($queries as $data) {
            $result = $db->exec_INSERTmultipleRows(
                $data['table'],
                $data['columns'],
                $data['values']
            );
            if ($result) {
                $firstId = $db->sql_insert_id();
                $maxId_plus_one = count($data['values']) + $firstId;
                for ($i = $firstId; $i < $maxId_plus_one ;$i++) {
                    $insertIds[] = $i;
                    if (isset($mm[$i-$firstId]) && !empty($mm[$i-$firstId])) {
                        $mm_row = $mm[$i-$firstId];
                        if (isset($mm_row['insert'])) {
                            foreach ($mm_row['insert'] as $subTypeData) {
                                $ids = $this->execInsertConcatenatedQueries($subTypeData, array($data['child'] => $i));
                            }
                        }
                    }
                }
            }
        }

        return $insertIds;
    }

    /**
     * @param array $sqlData
     * @param null|array $parentId
     * @return array
     */
    protected function execUpdateConcatenatedQueries(array $sqlData, $parentId = null)
    {
        foreach ($sqlData as $singleData) {
            $mm[] = $singleData['mm'];
        }

        // TODO: add update to DB

        // TODO: add sub mm call for insert and updates
    }

    /**
     * @param DomainObjectInterface $object
     * @return string
     */
    protected function findSqlType(\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface $object)
    {
        $type = 'update';
        if ($object->_isNew()) {
            $type = 'insert';
        }

        return $type;
    }

    /**
     * @return bool
     */
    public function isTransactionActive()
    {
        return self::$transaction;
    }
}
