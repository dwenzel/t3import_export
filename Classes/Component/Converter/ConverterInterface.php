<?php

declare(strict_types=1);
namespace CPSIT\T3importExport\Component\Converter;

use CPSIT\T3importExport\Component\ComponentInterface;
use CPSIT\T3importExport\Domain\Model\TaskResult;

/**
 * Interface ConverterInterface
 */
interface ConverterInterface extends ComponentInterface
{
    /**
     * @return mixed
     */
    public function convert(array $record, array $configuration);

    /**
     * Tells if the component is disabled
     */
    public function isDisabled(array $configuration, array $record = [], ?TaskResult $result = null): bool;

    /**
     * @return mixed
     */
    public function isConfigurationValid(array $configuration): bool;

    /**
     * Sets the configuration
     *
     * @return mixed
     */
    public function setConfiguration(array $configuration);

    /**
     * Returns the configuration
     */
    public function getConfiguration(): array;
}
