<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Factory;

use CPSIT\ImportExportCore\Factory\InstantiatorInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Creates the components of a task with GeneralUtility::makeInstance(), so that
 * public services get their dependencies. Each component is an independent
 * instance (clone), also if the class is a shared service.
 */
final class Typo3Instantiator implements InstantiatorInterface
{
    public function instantiate(string $className): object
    {
        return clone GeneralUtility::makeInstance($className);
    }
}
