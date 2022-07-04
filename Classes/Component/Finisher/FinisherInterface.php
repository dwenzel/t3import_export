<?php
namespace CPSIT\T3importExport\Component\Finisher;

use CPSIT\T3importExport\Component\ComponentInterface;
use CPSIT\T3importExport\Domain\Model\TaskResult;

/**
 * Interface FinisherInterface
 *
 * @package CPSIT\T3importExport\Component\Finisher
 */
interface FinisherInterface extends ComponentInterface
{
    /**
     * @param array $configuration
     * @param array $records Array with prepared records
     * @param array|\Iterator|null $result Array with result records
     * @return bool
     */
    public function process(array $configuration, array &$records, &$result): bool;

    /**
     * @param array $configuration
     * @return bool
     */
    public function isConfigurationValid(array $configuration);

    /**
     * Tells if the component is disabled
     * @param array $configuration
     * @param array $record
     * @param TaskResult|null $result
     * @return bool
     */
    public function isDisabled(array $configuration, array $record = [], TaskResult $result = null): bool;

    /**
     * Sets the configuration
     *
     * @param array $configuration
     */
    public function setConfiguration(array $configuration): void;

    /**
     * Returns the configuration
     *
     * @return array
     */
    public function getConfiguration(): array;
}
