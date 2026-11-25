<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Resource;

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

/**
 * Provides the FileTargetGuard to components which write files.
 */
trait FileTargetGuardTrait
{
    protected ?FileTargetGuard $fileTargetGuard = null;

    public function setFileTargetGuard(FileTargetGuard $fileTargetGuard): void
    {
        $this->fileTargetGuard = $fileTargetGuard;
    }

    protected function getFileTargetGuard(): FileTargetGuard
    {
        return $this->fileTargetGuard ??= new FileTargetGuard();
    }
}
