<?php
namespace CPSIT\T3importExport\Component\Initializer;

use CPSIT\T3importExport\Domain\Model\TaskResult;

/**
 * Interface InitializerInterface
 *
 * @package CPSIT\T3importExport\Component\Initializer
 */
interface InitializerInterface
{
    /**
     * @param array $configuration
     * @param array $records Array with prepared records
     * @return bool
     */
    public function process(array $configuration, array &$records): bool;

    /**
     * @param array $configuration
     * @return bool
     */
    public function isConfigurationValid(array $configuration): bool;

    /**
     * Tells if the component is disabled
     * @param array $configuration
     * @param array $record
     * @param TaskResult|\Iterator|array $result
     * @return mixed
     */
    public function isDisabled(array $configuration, array $record = [], TaskResult $result = null): bool;

    /**
     * Sets the configuration
     *
     * @param array $configuration
     * @return mixed
     */
    public function setConfiguration(array $configuration);

    /**
     * Returns the configuration
     *
     * @return array
     */
    public function getConfiguration(): array;
}
