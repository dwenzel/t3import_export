<?php
/**
 * Created by PhpStorm.
 * User: benjamin
 * Date: 19.05.16
 * Time: 14:57
 */

namespace CPSIT\T3importExport\Persistence;


use CPSIT\T3importExport\ConfigurableInterface;

class DataTargetStreamRepository extends DataTargetRepository implements DataTargetInterface, ConfigurableInterface
{
    protected $config;

    public function persist($object, array $configuration = null)
    {
        var_dump($object, $configuration);
        return null;
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