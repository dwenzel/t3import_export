<?php

namespace CPSIT\T3importExport\Tests\Unit\Persistence\Factory;

use CPSIT\T3importExport\ConfigurableTrait;
use CPSIT\T3importExport\IdentifiableInterface;
use CPSIT\T3importExport\IdentifiableTrait;
use CPSIT\T3importExport\InvalidConfigurationException;
use CPSIT\T3importExport\MissingClassException;
use CPSIT\T3importExport\MissingInterfaceException;
use CPSIT\T3importExport\Persistence\DataTargetInterface;
use CPSIT\T3importExport\Persistence\Factory\DataTargetFactory;
use CPSIT\T3importExport\Tests\Unit\Traits\MockObjectManagerTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2015 Dirk Wenzel <dirk.wenzel@cps-it.de>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Class DummyMissingTargetInterfaceClass
 *
 * @package CPSIT\T3importExport\Tests\Unit\Persistence\Factory
 */
class DummyMissingTargetInterfaceClass
{
}

/**
 * Class DummyTargetObjectClass
 *
 * @package CPSIT\T3importExport\Tests\Unit\Persistence\Factory
 */
class DummyTargetObjectClass
{
}

/**
 * Class DummyIdentifiableTargetInterfaceClass
 */
class DummyIdentifiableTargetInterfaceClass implements DataTargetInterface, IdentifiableInterface
{
    use IdentifiableTrait, ConfigurableTrait;

    /**
     * Fake method matches DataTargetInterface
     *
     * @param array $configuration
     * @return array
     */
    public function testGetRecords(array $configuration): array
    {
        return [];
    }

    /**
     * Fake method matches abstract method in DataTargetInterface
     *
     * @param array $configuration
     * @return bool
     */
    public function isConfigurationValid(array $configuration): bool
    {
        return true;
    }

    /**
     * Fake method matches abstract method in ConfigurableInterface
     *
     * @param $object
     * @param array|null $configuration
     * @return bool
     * @noinspection PhpMissingReturnTypeInspection
     * @noinspection ReturnTypeCanBeDeclaredInspection
     * @noinspection PhpInconsistentReturnPointsInspection
     */
    public function persist($object, array $configuration = null)
    {
    }

    /**
     * Dummy method
     * Doesn't do anything
     *
     * @param null $result
     * @param array|null $configuration
     * @return void
     * @noinspection ReturnTypeCanBeDeclaredInspection
     */
    public function persistAll($result = null, array $configuration = null)
    {
    }
}

/**
 * Class DataTargetFactoryTest
 *
 * @package CPSIT\T3importExport\Tests\Unit\Persistence\Factory
 * @coversDefaultClass \CPSIT\T3importExport\Persistence\Factory\DataTargetFactory
 */
class DataTargetFactoryTest extends TestCase
{
    use MockObjectManagerTrait;

    protected DataTargetFactory $subject;

    /**
     * @var DataTargetInterface|MockObject
     */
    protected DataTargetInterface $dataTarget;

    /**
     * set up
     * @noinspection ReturnTypeCanBeDeclaredInspection
     */
    public function setUp()
    {
        $this->subject = new DataTargetFactory();
        $this->mockObjectManager();
        $this->mockDataTarget();
    }

    protected function mockDataTarget(): void
    {
        $this->dataTarget = $this->getMockBuilder(DummyIdentifiableTargetInterfaceClass::class)
            ->setMethods(['setIdentifier'])
            ->getMock();
    }

    /**
     * @throws MissingClassException
     * @throws InvalidConfigurationException
     * @throws MissingInterfaceException
     */
    public function testGetThrowsExceptionForMissingTargetClass(): void
    {
        $this->expectExceptionCode(1451043513);
        $this->expectException(MissingClassException::class);
        $identifier = 'foo';
        $settings = [
            'class' => 'NonExistingTargetClass'
        ];
        $this->subject->get($settings, $identifier);
    }

    /**
     * @throws MissingClassException
     * @throws InvalidConfigurationException
     * @throws MissingInterfaceException
     */
    public function testGetThrowsExceptionForMissingInterface(): void
    {
        $this->expectExceptionCode(1451045997);
        $this->expectException(MissingInterfaceException::class);
        $identifier = 'foo';
        $settings = [
            'class' => DummyMissingTargetInterfaceClass::class
        ];
        $this->subject->get($settings, $identifier);
    }

    /**
     * @throws MissingClassException
     * @throws InvalidConfigurationException
     * @throws MissingInterfaceException
     */
    public function testGetThrowsExceptionForMissingObjectClass(): void
    {
        $this->expectException(MissingClassException::class);
        $this->expectExceptionCode(1451043367);
        $identifier = 'foo';
        $settings = [
            'object' => [
                'class' => 'NonExistingObjectClass'
            ]
        ];
        $this->subject->get($settings, $identifier);
    }

    /**
     * @throws MissingClassException
     * @throws InvalidConfigurationException
     * @throws MissingInterfaceException
     */
    public function testGetReturnsDefaultDataTarget(): void
    {
        $identifier = 'foo';
        $objectClass = DummyTargetObjectClass::class;
        $dataTargetClass = DataTargetFactory::DEFAULT_DATA_TARGET_CLASS;
        $this->objectManager->expects($this->once())
            ->method('get')
            ->with(...[$dataTargetClass, $objectClass]);
        $settings = [
            'object' => [
                'class' => $objectClass
            ]
        ];
        $this->subject->get($settings, $identifier);
    }

    /**
     * @throws MissingClassException
     * @throws InvalidConfigurationException
     * @throws MissingInterfaceException
     */
    public function testGetSetsIdentifierForIdentifiableTarget(): void
    {
        $identifier = 'foo';
        $dataSourceClass = DummyIdentifiableTargetInterfaceClass::class;
        $settings = [
            'class' => $dataSourceClass,
            'identifier' => 'barSourceIdentifier',
            'config' => []
        ];
        $this->dataTarget->expects($this->once())
            ->method('setIdentifier')
            ->with($settings['identifier']);
        $this->objectManager->expects($this->once())
            ->method('get')
            ->with(...[$dataSourceClass])
            ->willReturn($this->dataTarget);

        $this->subject->get($settings, $identifier);
    }
}
