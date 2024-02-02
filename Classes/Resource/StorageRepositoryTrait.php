<?php

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

use TYPO3\CMS\Core\Resource\StorageRepository;

/**
 * Trait StorageRepositoryTrait
 */
trait StorageRepositoryTrait
{
    /**
     * @var StorageRepository
     */
    protected $storageRepository;

    /**
     * Injects the resourceStorage repository
     *
     * @param StorageRepository $storageRepository
     */
    public function injectStorageRepository(StorageRepository $storageRepository): void
    {
        $this->storageRepository = $storageRepository;
    }

    public function getStorageRepository(): StorageRepository
    {
        return $this->storageRepository;
    }
}
