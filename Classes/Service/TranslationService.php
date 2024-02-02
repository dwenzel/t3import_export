<?php

namespace CPSIT\T3importExport\Service;

/***************************************************************
 *  Copyright notice
 *  (c) Artus Kolanowski <artus@ionoi.net>
 *  see https://gist.github.com/witrin/764bc856decf26c5e784
 *
 *  All rights reserved
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use CPSIT\T3importExport\InvalidColumnMapException;
use Exception;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\TableColumnType;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

/**
 * Provides services to translate domain objects
 */
class TranslationService implements DomainObjectTranslatorInterface, SingletonInterface
{
    final public const MISSING_COLUMN_MAP_EXCEPTION_CODE = 1_641_229_990;
    final public const MISSING_COLUMN_MAP_MESSAGE = 'Missing column map for property %s';

    /**
     * @var DataMapper
     */
    protected DataMapper $dataMapper;
    protected PersistenceManagerInterface $persistenceManager;

    public function __construct(DataMapper $dataMapper = null, PersistenceManagerInterface $persistenceManager = null)
    {
        if ($dataMapper === null) {
            /** @var ObjectManager $objectManager */
            $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
            /** @noinspection CallableParameterUseCaseInTypeContextInspection */
            $dataMapper = $objectManager->get(DataMapper::class);
        }
        /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
        $this->dataMapper = $dataMapper;
        $this->persistenceManager = $persistenceManager ?? GeneralUtility::makeInstance(PersistenceManager::class);
    }

    /**
     * Translates a domain object
     *
     * @param DomainObjectInterface $origin
     * @param DomainObjectInterface $translation
     * @param int $language Language id
     * @return void
     * @throws Exception
     * @throws InvalidColumnMapException
     */
    public function translate(DomainObjectInterface $origin, DomainObjectInterface $translation, int $language): void
    {
        if (!$this->haveSameClass($origin, $translation)) {
            throw new Exception('Origin and translation must be the same type.', 1432499926);
        }

        if ($origin === $translation) {
            throw new Exception('Origin can\'t be translation of its own.', 1432502696);
        }

        $dataMap = $this->dataMapper->getDataMap($origin::class);

        if (!$dataMap->getTranslationOriginColumnName()) {
            throw new Exception('The type is not translatable.', 1432500079);
        }

        $propertyName = GeneralUtility::underscoredToLowerCamelCase($dataMap->getTranslationOriginColumnName());

        if ($translation->_setProperty($propertyName, $origin) === false) {
            $columnMap = $dataMap->getColumnMap($propertyName);
            if ($columnMap === null) {
                $message = sprintf(self::MISSING_COLUMN_MAP_MESSAGE, $propertyName);
                throw new InvalidColumnMapException(
                    $message,
                    self::MISSING_COLUMN_MAP_EXCEPTION_CODE
                );
            }
            $columnMap->setTypeOfRelation(ColumnMap::RELATION_HAS_ONE);
            /**
             * fixme we set a default TableColumnType here. This may not be necessary
             * enumeration @see TableColumnType was probably introduced later than
             * the reference implementation we rely on
             */
            $type = new TableColumnType();
            $columnMap->setType($type);
            $columnMap->setChildTableName($dataMap->getTableName());

            $translation->{$propertyName} = $origin;
        }

        //fixme This magic method is marked internal. We should not use it.
        $translation->_setProperty('_languageUid', $language);
    }

    /**
     * Tells if two object are instances of the same class
     *
     * @param DomainObjectInterface $origin
     * @param DomainObjectInterface $translation
     * @return bool
     */
    public function haveSameClass(DomainObjectInterface $origin, DomainObjectInterface $translation): bool
    {
        return $origin::class === $translation::class;
    }

    /**
     * @param $identity
     * @param $targetType
     * @return object
     */
    public function getLocalizationParent($identity, $targetType): ?object
    {
        $query = $this->persistenceManager->createQueryForType($targetType);
        $querySettings = $query->getQuerySettings();

        $querySettings->setIgnoreEnableFields(true);
        $querySettings->setRespectStoragePage(false);
        $querySettings->setLanguageUid(0);
        $query->setQuerySettings($querySettings);
        $parentObject = $query->matching($query->equals('uid', $identity))->execute()->getFirst();

        return $parentObject;
    }

    /**
     * Returns localizations as array of records [sic!].
     * This method is a wrapper for a static method call of the core BackendUtility
     *
     * @param string $table
     * @param int $uid
     * @param int $language
     * @return array
     */
    public function getRecordLocalization(string $table, int $uid, int $language): array
    {
        if ($result = BackendUtility::getRecordLocalization($table, $uid, $language)) {
            return $result;
        }

        return [];
    }
}
