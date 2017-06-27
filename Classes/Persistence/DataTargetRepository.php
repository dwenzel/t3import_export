<?php
namespace CPSIT\T3importExport\Persistence;

use CPSIT\T3importExport\ConfigurableInterface;
use CPSIT\T3importExport\ConfigurableTrait;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Object\UnknownClassException;
use TYPO3\CMS\Extbase\Persistence\Repository;

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


    /**
     * @var \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface
     */
    protected $persistenceManager;


    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * Constructor
     *
     * @param string $targetClass
     */
    public function __construct($targetClass)
    {
        $this->targetClass = $targetClass;
    }

    /**
     * injects the object manager
     *
     * @param ObjectManager $objectManager
     */
    public function injectObjectManager(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface $persistenceManager
     */
    public function injectPersistenceManager(\TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface $persistenceManager)
    {
        $this->persistenceManager = $persistenceManager;
    }

    /**
     * Persist both new and updated objects.
     *
     * @param DomainObjectInterface $object Record to persist. Either an array or an instance of \TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject
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
     * @param array|null $result
     * @param array|null $configuration
     * @return mixed
     */
    public function persistAll($result = null, array $configuration = null)
    {
        $this->persistenceManager->persistAll();
    }


    /**
     * Gets the repository
     *
     * @return Repository
     * @throws UnknownClassException
     */
    protected function getRepository()
    {
        if (!$this->repository instanceof Repository) {
            $repositoryClass = str_replace('Model', 'Repository', $this->targetClass) . 'Repository';
            if (class_exists($repositoryClass)) {
                /** Repository $this->repository */
                $this->repository = $this->objectManager->get($repositoryClass);
            } else {
                throw new UnknownClassException();
            }
        }

        return $this->repository;
    }

    /**
     * @return string
     */
    public function getTargetClass()
    {
        return $this->targetClass;
    }
}
