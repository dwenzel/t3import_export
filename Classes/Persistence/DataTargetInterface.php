<?php
namespace CPSIT\T3import\Persistence;

use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;

/**
 * Interface DataTargetInterface
 *
 *
 *
 * @package CPSIT\T3import\Persistence
 */
interface DataTargetInterface {
	/**
	 * Persist both new and updated records.
	 *
	 * @param array|DomainObjectInterface $object Record to persist. Either an array or an instance of \TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject
	 * @param array $configuration Configuration array.
	 * @return mixed
	 */
	public function persist($object, array $configuration);
}