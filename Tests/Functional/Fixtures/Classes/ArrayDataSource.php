<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Functional\Fixtures\Classes;

use CPSIT\ImportExportCore\ConfigurableInterface;
use CPSIT\ImportExportCore\ConfigurableTrait;
use CPSIT\ImportExportCore\Persistence\DataSourceInterface;

/**
 * Returns the records of the option `records`
 */
final class ArrayDataSource implements DataSourceInterface, ConfigurableInterface
{
    use ConfigurableTrait;

    public function isConfigurationValid(array $configuration): bool
    {
        return \is_array($configuration['records'] ?? null);
    }

    public function getRecords(array $configuration): iterable
    {
        return $configuration['records'];
    }
}
