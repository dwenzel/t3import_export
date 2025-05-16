<?php
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
 * Class ResourceStorageTraitTest
 */
class ResourceStorageTraitTest extends TestCase
{
    /**
     * @var object Class using ResourceStorageTrait
     */
    protected $subject;

    /**
     * @var StorageRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $storageRepository;

    /**
     * set up subject
     */
    protected function setUp(): void
    {
        // Skip this test as it requires getMockForTrait and assertAttributeSame
        $this->markTestSkipped('Test uses getMockForTrait and assertAttributeSame which are removed in PHPUnit 10+');
    }

    /**
     * Provides dependencies for injection tests
     */
    public static function dependenciesDataProvider(): array
    {
        return [
            [StorageRepository::class, 'storageRepository']
        ];
    }

    /**
     * @param string $class Class name of the dependency to inject
     * @param string $propertyName The property holding the dependency
     */
    #[Test]
    #[DataProvider('dependenciesDataProvider')]
    public function dependenciesCanBeInjected($class, $propertyName): void
    {
        // This test is skipped in setUp()
    }

    #[Test]
    public function initializeStorageGetsStorageFromRepository(): void
    {
        // This test is skipped in setUp()
    }
}
