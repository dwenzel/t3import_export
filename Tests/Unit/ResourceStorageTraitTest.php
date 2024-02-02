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
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;

/**
 * Class ResourceStorageTraitTest
 */
class ResourceStorageTraitTest extends TestCase
{
    /**
     * @var \CPSIT\T3importExport\Resource\ResourceStorageTrait |\PHPUnit_Framework_MockObject_MockObject
     */
    protected $subject;

    /**
     * @var \TYPO3\CMS\Core\Resource\StorageRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $storageRepository;

    /**
     * set up subject
     */
    protected function setUp(): void
    {
        $this->subject = $this->getMockBuilder(ResourceStorageTrait::class)
            ->getMockForTrait();

        $this->storageRepository = $this->getMockBuilder(StorageRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['findByUid'])->getMock();

        $this->subject->injectStorageRepository($this->storageRepository);
    }

    /**
     * Provides dependencies for injection tests
     */
    public function dependenciesDataProvider()
    {
        return [
            [StorageRepository::class, 'storageRepository']
        ];
    }

    /**
     * @test
     * @dataProvider dependenciesDataProvider
     * @param string $class Class name of the dependency to inject
     * @param string $propertyName The property holding the dependency
     */
    public function dependenciesCanBeInjected($class, $propertyName)
    {
        $mockDependency = $this->getMockBuilder($class)->disableOriginalConstructor()
            ->getMock();

        $methodName = 'inject' . ucfirst($propertyName);
        $this->subject->{$methodName}($mockDependency);

        $this->assertAttributeSame(
            $mockDependency,
            $propertyName,
            $this->subject
        );
    }

    /**
     * @test
     */
    public function initializeStorageGetsStorageFromRepository()
    {
        $configuration = [
            'storageId' => 3
        ];
        $mockStorage = $this->getMockBuilder(ResourceStorage::class)->disableOriginalConstructor()
            ->getMock();

        $this->storageRepository->expects($this->once())->method('findByUid')
            ->with($configuration['storageId'])
            ->will($this->returnValue($mockStorage));

        $this->subject->initializeStorage($configuration);

        $this->assertAttributeSame(
            $mockStorage,
            'resourceStorage',
            $this->subject
        );
    }
}
