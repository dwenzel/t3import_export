<?php
namespace CPSIT\T3import\Service;

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

use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Provides services to translate domain objects
 */
class TranslationService implements SingletonInterface {

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper
	 * @inject
	 */
	protected $dataMapper;

	/**
	 * Translates a domain object
	 *
	 * @param DomainObjectInterface $origin
	 * @param DomainObjectInterface $translation
	 * @param int $language
	 * @throws \Exception
	 * @return void
	 */
	public function translate(DomainObjectInterface $origin, DomainObjectInterface $translation, $language) {
		if (get_class($origin) !== get_class($translation)) {
			throw new \Exception('Origin and translation must be the same type.', 1432499926);
		}

		if ($origin === $translation) {
			throw new \Exception('Origin can\'t be translation of its own.', 1432502696);
		}

		$dataMap = $this->dataMapper->getDataMap(get_class($origin));

		if (!$dataMap->getTranslationOriginColumnName()) {
			throw new \Exception('The type is not translatable.', 1432500079);
		}

		$propertyName = GeneralUtility::underscoredToLowerCamelCase($dataMap->getTranslationOriginColumnName());

		if ($translation->_setProperty($propertyName, $origin) === FALSE) {
			$columnMap = $dataMap->getColumnMap($propertyName);
			$columnMap->setTypeOfRelation(ColumnMap::RELATION_HAS_ONE);
			$columnMap->setType($dataMap->getClassName());
			$columnMap->setChildTableName($dataMap->getTableName());

			$translation->{$propertyName} = $origin;
		}

		$translation->_setProperty('_languageUid', $language);
	}
}
