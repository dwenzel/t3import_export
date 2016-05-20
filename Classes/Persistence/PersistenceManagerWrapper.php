<?php
/**
 * Created by PhpStorm.
 * User: benjamin
 * Date: 19.05.16
 * Time: 14:33
 */

namespace CPSIT\T3importExport\Persistence;


use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

class PersistenceManagerWrapper implements PersistenceManagerInterface
{
    /**
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * PersistenceManagerWrapper constructor.
     * @param PersistenceManagerInterface $persistenceManager
     */
    public function __construct(PersistenceManagerInterface $persistenceManager)
    {
        $this->persistenceManager = $persistenceManager;
    }

    public function persistAll()
    {
        $this->persistenceManager->persistAll();
    }

    public function clearState()
    {
        $this->persistenceManager->clearState();
    }

    public function isNewObject($object)
    {
        return $this->persistenceManager->isNewObject($object);
    }

    public function getIdentifierByObject($object)
    {
        return $this->persistenceManager->getIdentifierByObject($object);
    }

    public function getObjectByIdentifier($identifier, $objectType = null, $useLazyLoading = false)
    {
        return $this->persistenceManager->getObjectByIdentifier($identifier, $objectType, $useLazyLoading);
    }

    public function getObjectCountByQuery(QueryInterface $query)
    {
        return $this->persistenceManager->getObjectCountByQuery($query);
    }

    public function getObjectDataByQuery(QueryInterface $query)
    {
        return $this->persistenceManager->getObjectDataByQuery($query);
    }

    public function registerRepositoryClassName($className)
    {
        $this->persistenceManager->registerRepositoryClassName($className);
    }

    public function add($object)
    {
        $this->persistenceManager->add($object);
    }

    public function remove($object)
    {
        $this->persistenceManager->remove($object);
    }

    public function update($object)
    {
        $this->persistenceManager->update($object);
    }

    public function injectSettings(array $settings)
    {
        $this->persistenceManager->injectSettings($settings);
    }

    public function convertObjectToIdentityArray($object)
    {
        return $this->persistenceManager->convertObjectToIdentityArray($object);
    }

    public function convertObjectsToIdentityArrays(array $array)
    {
        return $this->persistenceManager->convertObjectsToIdentityArrays($array);
    }

    public function createQueryForType($type)
    {
        return $this->persistenceManager->createQueryForType($type);
    }
}