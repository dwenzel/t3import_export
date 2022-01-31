<?php
namespace CPSIT\T3importExport;

/**
 * Class ConfigurableTrait
 *
 * @package CPSIT\T3importExport
 */
trait ConfigurableTrait
{
    /**
     * Configuration for this component
     * A plain TypoScript array
     *
     * @var array
     */
    protected array $configuration = [];

    /**
     * Tells if a given configuration is valid
     *
     * @param array $configuration
     * @return bool
     */
    abstract public function isConfigurationValid(array $configuration): bool;

    /**
     * @return array
     */
    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    /**
     * Sets the configuration if it is valid.
     * Throws an exception otherwise.
     *
     * @param array $configuration
     * @throws InvalidConfigurationException
     */
    public function setConfiguration(array $configuration): void
    {
        if ($this->isConfigurationValid($configuration)) {
            $this->configuration = $configuration;
        } else {
            throw new InvalidConfigurationException(
                'Configuration for ' . __CLASS__
                . ' is not valid.',
                1451659793
            );
        }
    }
}
