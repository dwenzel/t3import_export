<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Functional\Fixtures\Classes;

use CPSIT\ImportExportCore\Persistence\DataTargetInterface;

/**
 * Keeps the persisted records in a static property
 */
final class CollectingDataTarget implements DataTargetInterface
{
    /**
     * @var list<array<array-key, mixed>|object>
     */
    public static array $records = [];

    public function isConfigurationValid(array $configuration): bool
    {
        return true;
    }

    public function persist(array|object $object, ?array $configuration = null): bool
    {
        self::$records[] = $object;

        return true;
    }

    public function persistAll(mixed $result = null, ?array $configuration = null): void {}
}
