<?php
/**
 * Created by PhpStorm.
 * User: benjamin
 * Date: 19.05.16
 * Time: 14:57
 */

namespace CPSIT\T3importExport\Persistence;


use CPSIT\T3importExport\ConfigurableInterface;
use CPSIT\T3importExport\Domain\Model\DataStream;

class DataTargetStreamRepository extends DataTargetRepository implements DataTargetInterface, ConfigurableInterface
{
    protected $config;

    /**
     * @param DataStream $object
     * @param array|null $configuration
     */
    public function persist($object, array $configuration = null)
    {
        // TODO: add memory saving config check
        // if memory saving true ... dont persist buffer in object
        // write it to typo3Temp directory and save the file path in setTmpFile($file)
        if (is_a($object, DataStream::class)) {
            $object->generateOutput();
        }
    }

    /**
     * @param array|null|null $result
     * @param array|null|null $configuration
     * @return mixed
     */
    public function persistAll(array $result = null, array $configuration = null)
    {
        // if memory saving true create an base xmlFile and put record by record in the base file ...
        // otherwise use the record buffer
        var_dump($result, $configuration);
        //$this->persistenceManager->persistAll();
    }
    

    public function isConfigurationValid(array $configuration)
    {
        return true;
    }

    public function getConfiguration()
    {
        return $this->config;
    }

    public function setConfiguration(array $configuration)
    {
        $this->config = $configuration;
    }
}