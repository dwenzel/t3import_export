<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Persistence;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use CPSIT\ImportExportCore\Persistence\DataTargetInterface;
use CPSIT\ImportExportCore\Persistence\NullDataTarget;
use CPSIT\T3importExport\Utility\DeprecationUtility;

/**
 * Writes nothing.
 *
 * @deprecated since 4.0, will be removed in 5.0. Use
 * `CPSIT\ImportExportCore\Persistence\NullDataTarget`.
 */
class DataTargetDummy implements DataTargetInterface
{
    public function persist(array|object $object, ?array $configuration = null): bool
    {
        DeprecationUtility::trigger(
            self::class . ' is deprecated since 4.0. Use ' . NullDataTarget::class . '.',
            DeprecationUtility::FILE_COMPONENTS_ISSUE_URL
        );

        return true;
    }

    public function persistAll(mixed $result = null, ?array $configuration = null): void {}
}
