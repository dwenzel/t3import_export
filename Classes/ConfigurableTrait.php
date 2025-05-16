<?php

declare(strict_types=1);

namespace CPSIT\T3importExport;

/**
 * Class ConfigurableTrait
 */
trait ConfigurableTrait
{
    /**
     * Configuration for this component
     * A plain TypoScript array
     */
    protected array $configuration = [];

    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    /**
     * Sets the configuration if it is valid.
     * Throws an exception otherwise.
     *
     * @throws InvalidConfigurationException
     */
    public function setConfiguration(array $configuration): void
    {
        if (!$this->isConfigurationValid($configuration)) {
            throw new InvalidConfigurationException(
                'Configuration for ' . $this::class
                . ' is not valid.',
                1_451_659_793
            );
        }

        $this->configuration = $configuration;
    }

    /**
     * Tells if a given configuration is valid
     */
    abstract public function isConfigurationValid(array $configuration): bool;
}
