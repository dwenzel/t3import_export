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

/**
 * Trait ResourceStorageTrait
 * Provides a resource storage
 */
trait ResourceStorageTrait
{
    use StorageRepositoryTrait;
    /**
     * @var \TYPO3\CMS\Core\Resource\ResourceStorage
     */
    protected $resourceStorage;

    /**
     * Initializes the resource resourceStorage
     *
     * @param array $configuration
     */
    public function initializeStorage($configuration)
    {
        $this->resourceStorage = $this->storageRepository->findByUid($configuration['storageId']);
    }
}
