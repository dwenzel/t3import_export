<?php

declare(strict_types=1);
namespace CPSIT\T3importExport\Tests\Unit;

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

use CPSIT\T3importExport\Resource\ResourceStorageTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;

/**
 * Concrete class for testing ResourceStorageTrait
 */
class ResourceStorageTraitTestClass
{
    use ResourceStorageTrait;
    
    public function getStorageRepository(): ?StorageRepository
    {
        return $this->storageRepository ?? null;
    }
}

/**
 * Class ResourceStorageTraitTest
 */
class ResourceStorageTraitTest extends TestCase
{
    protected ResourceStorageTraitTestClass $subject;
    protected StorageRepository $storageRepository;

    protected function setUp(): void
    {
        $this->subject = new ResourceStorageTraitTestClass();
        $this->storageRepository = $this->createMock(StorageRepository::class);
    }

    /**
     * Provides dependencies for injection tests
     */
    public static function dependenciesDataProvider(): array
    {
        return [
            [StorageRepository::class, 'storageRepository'],
        ];
    }

    #[Test]
    #[DataProvider('dependenciesDataProvider')]
    public function dependenciesCanBeInjected(string $class, string $propertyName): void
    {
        $this->subject->injectStorageRepository($this->storageRepository);
        $this->assertInstanceOf($class, $this->subject->getStorageRepository());
    }

    #[Test]
    public function initializeStorageGetsStorageFromRepository(): void
    {
        $storageId = 1;
        $configuration = ['storageId' => $storageId];
        $mockStorage = $this->createMock(ResourceStorage::class);
        
        $this->storageRepository->expects($this->once())
            ->method('findByUid')
            ->with($storageId)
            ->willReturn($mockStorage);
            
        $this->subject->injectStorageRepository($this->storageRepository);
        $this->subject->initializeStorage($configuration);
        
        // We can't easily test the internal resourceStorage property 
        // without exposing it, but we can verify the method was called
        $this->assertTrue(true); // Test passes if no exception is thrown
    }
}
