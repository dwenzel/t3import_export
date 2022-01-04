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
use TYPO3\CMS\Core\DataHandling\TableColumnType;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;

/**
 * Provides services to translate domain objects
 */
class TranslationService implements DomainObjectTranslatorInterface, SingletonInterface
{
    public const MISSING_COLUMN_MAP_EXCEPTION_CODE = 1641229990;
    public const MISSING_COLUMN_MAP_MESSAGE = 'Missing column map for property %s';

    /**
     * @var DataMapper
     */
    protected DataMapper $dataMapper;

    /**
     * @param DataMapper $dataMapper
     */
    public function injectDataMapper(DataMapper $dataMapper): void
    {
        $this->dataMapper = $dataMapper;
    }

    /**
     * Translates a domain object
     *
     * @param DomainObjectInterface $origin
     * @param DomainObjectInterface $translation
     * @param int $language
     * @return void
     * @throws Exception|InvalidColumnMapException
     */
    public function translate(DomainObjectInterface $origin, DomainObjectInterface $translation, $language)
    {
        if (!$this->haveSameClass($origin, $translation)) {
            throw new Exception('Origin and translation must be the same type.', 1432499926);
        }

        if ($origin === $translation) {
            throw new Exception('Origin can\'t be translation of its own.', 1432502696);
        }

        $dataMap = $this->dataMapper->getDataMap(get_class($origin));

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
        return get_class($origin) === get_class($translation);
    }
}
