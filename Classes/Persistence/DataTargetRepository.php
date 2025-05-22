<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Persistence;

use CPSIT\ImportExportCore\Exception\MissingClassException;
use CPSIT\ImportExportCore\Persistence\DataTargetInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;
use TYPO3\CMS\Extbase\Persistence\RepositoryInterface;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2015 Dirk Wenzel <dirk.wenzel@cps-it.de>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
class DataTargetRepository implements DataTargetInterface
{
    public const int MISSING_CLASS_EXCEPTION_CODE = 1_641_374_612;
    public const string MISSING_CLASS_EXCEPTION_MESSAGE = 'Could not find repository class %s for object of type %s';

    /**
     * Constructor
     */
    public function __construct(
        protected string $targetClass,
        protected ?RepositoryInterface $repository,
        protected PersistenceManagerInterface $persistenceManager
    ) {}

    /**
     * Persist both new and updated objects.
     *
     * @param DomainObjectInterface|array $object Record to persist. Either an array or an instance of \TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject
     * @param array $configuration Configuration array.
     * @return mixed
     */
    public function persist($object, ?array $configuration = null): mixed
    {
        $repository = $this->getRepository();
        if (!$this->persistenceManager->isNewObject($object)) {
            $repository->update($object);
        } else {
            $repository->add($object);
        }

        return null;
    }

    /**
     * Gets the repository
     *
     * @throws MissingClassException
     */
    public function getRepository(): Repository
    {
        if (!$this->repository instanceof Repository) {
            $repositoryClass = str_replace('Model', 'Repository', $this->targetClass) . 'Repository';
            if (class_exists($repositoryClass)) {
                // fixme - This can not be tested easily
                /** Repository $this->repository */
                $this->repository = GeneralUtility::makeInstance($repositoryClass);
            } else {
                $message = sprintf(
                    self::MISSING_CLASS_EXCEPTION_MESSAGE,
                    $repositoryClass,
                    $this->targetClass
                );
                throw new MissingClassException(
                    $message,
                    self::MISSING_CLASS_EXCEPTION_CODE
                );
            }
        }

        return $this->repository;
    }

    /**
     * @param array|null $result
     * @return mixed
     */
    public function persistAll($result = null, ?array $configuration = null)
    {
        $this->persistenceManager->persistAll();
    }

    /**
     * @return string
     */
    public function getTargetClass()
    {
        return $this->targetClass;
    }
}
