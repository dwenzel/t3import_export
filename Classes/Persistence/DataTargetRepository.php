<?php

namespace CPSIT\T3importExport\Persistence;

use CPSIT\T3importExport\MissingClassException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
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
    public const MISSING_CLASS_EXCEPTION_CODE = 1641374612;
    public const MISSING_CLASS_EXCEPTION_MESSAGE = 'Could not find repository class %s for object of type %s';

    /**
     * Fully qualified class name of the object which should be persisted.
     * The object must extend the \TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject class.
     *
     * @var string
     */
    protected $targetClass;

    /**
     * @var Repository
     */
    protected $repository;


    protected PersistenceManagerInterface $persistenceManager;


    /**
     * Constructor
     *
     * @param string $targetClass
     * @param RepositoryInterface|null $repository
     * @param PersistenceManagerInterface|null $persistenceManager
     */
    public function __construct(string $targetClass, RepositoryInterface $repository = null, PersistenceManagerInterface $persistenceManager = null)
    {
        $this->targetClass = $targetClass;
        $this->repository = $repository;
        if ($persistenceManager === null) {
            $persistenceManager = (GeneralUtility::makeInstance(ObjectManager::class))
                ->get(PersistenceManagerInterface::class);
        }
        if (null !== $persistenceManager) {
            $this->persistenceManager = $persistenceManager;
        }
    }

    /**
     * Persist both new and updated objects.
     *
     * @param DomainObjectInterface|array $object Record to persist. Either an array or an instance of \TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject
     * @param array $configuration Configuration array.
     * @return mixed
     */
    public function persist($object, array $configuration = null)
    {
        $repository = $this->getRepository();
        if (!$this->persistenceManager->isNewObject($object)) {
            $repository->update($object);
        } else {
            $repository->add($object);
        }
    }

    /**
     * Gets the repository
     *
     * @return Repository
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
     * @param array|null $configuration
     * @return mixed
     */
    public function persistAll($result = null, array $configuration = null)
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
