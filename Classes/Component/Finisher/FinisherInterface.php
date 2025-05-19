<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Component\Finisher;

use CPSIT\T3importExport\Component\ComponentInterface;
use CPSIT\T3importExport\Domain\Model\TaskResult;

/**
 * Interface FinisherInterface
 */
interface FinisherInterface extends ComponentInterface
{
    /**
     * @param array $configuration
     * @param array $records Array with prepared records
     * @param array|object $result Array with result records
     * @return bool
     */
    public function process(array $configuration, array $records, array|object $result): bool;

    /**
     * @return bool
     */
    public function isConfigurationValid(array $configuration): bool;

    /**
     * Tells if the component is disabled
     */
    public function isDisabled(array $configuration, array $record = [], ?TaskResult $result = null): bool;

    /**
     * Sets the configuration
     */
    public function setConfiguration(array $configuration): void;

    /**
     * Returns the configuration
     */
    public function getConfiguration(): array;
}
