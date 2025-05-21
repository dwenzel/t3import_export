<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Persistence\Factory;

use CPSIT\ImportExportCore\ConfigurableTrait;
use CPSIT\ImportExportCore\IdentifiableInterface;
use CPSIT\ImportExportCore\IdentifiableTrait;
use CPSIT\ImportExportCore\Exception\MissingClassException;
use CPSIT\ImportExportCore\Exception\MissingInterfaceException;
use CPSIT\ImportExportCore\Persistence\DataTargetInterface;
use CPSIT\T3importExport\Persistence\Factory\DataTargetFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

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
 */
class DummyMissingTargetInterfaceClass
{
}

/**
 * Class DummyTargetObjectClass
 */
class DummyTargetObjectClass
{
}

/**
 * Class DummyIdentifiableTargetInterfaceClass
 */
class DummyIdentifiableTargetInterfaceClass implements DataTargetInterface, IdentifiableInterface
{
    use IdentifiableTrait;
    use ConfigurableTrait;

    /**
     * Fake method matches DataTargetInterface
     */
    public function testGetRecords(array $configuration): array
    {
        return [];
    }

    /**
     * Fake method matches abstract method in DataTargetInterface
     */
    public function isConfigurationValid(array $configuration): bool
    {
        return true;
    }

    /**
     * Fake method matches abstract method in ConfigurableInterface
     */
    public function persist($object, ?array $configuration = null)
    {
    }

    /**
     * Dummy method
     * Doesn't do anything
     *
     * @param null $result
     * @return void
     * @noinspection ReturnTypeCanBeDeclaredInspection
     */
    public function persistAll($result = null, ?array $configuration = null)
    {
    }
}

/**
 * Class DataTargetFactoryTest
 *
 * @coversDefaultClass \CPSIT\T3importExport\Persistence\Factory\DataTargetFactory
 */
class DataTargetFactoryTest extends TestCase
{
    protected DataTargetFactory $subject;

    /**
     * @var PersistenceManagerInterface|MockObject
     */
    protected PersistenceManagerInterface|MockObject $persistenceManager;

    /**
     * @var DataTargetInterface|MockObject
     */
    protected DataTargetInterface|MockObject $dataTarget;

    /**
     * set up
     */
    protected function setUp(): void
    {
        // Create persistence manager mock directly
        $this->persistenceManager = $this->createMock(PersistenceManagerInterface::class);

        $this->subject = new DataTargetFactory($this->persistenceManager);
    }

    #[Test]
    public function testGetThrowsExceptionForMissingTargetClass(): void
    {
        $this->expectExceptionCode(1_451_043_513);
        $this->expectException(MissingClassException::class);
        $identifier = 'foo';
        $settings = [
            'class' => 'NonExistingTargetClass',
        ];
        $this->subject->get($settings, $identifier);
    }

    #[Test]
    public function testGetThrowsExceptionForMissingInterface(): void
    {
        $this->expectExceptionCode(1_451_045_997);
        $this->expectException(MissingInterfaceException::class);
        $identifier = 'foo';
        $settings = [
            'class' => DummyMissingTargetInterfaceClass::class,
        ];
        $this->subject->get($settings, $identifier);
    }

    #[Test]
    public function testGetThrowsExceptionForMissingObjectClass(): void
    {
        $this->expectException(MissingClassException::class);
        $this->expectExceptionCode(1_451_043_367);
        $identifier = 'foo';
        $settings = [
            'object' => [
                'class' => 'NonExistingObjectClass',
            ],
        ];
        $this->subject->get($settings, $identifier);
    }

    #[Test]
    public function testGetReturnsDefaultDataTarget(): void
    {
        $this->markTestSkipped('DataTargetRepository requires constructor arguments in PHPUnit 12');

        /** @phpstan-ignore deadCode.unreachable */
        $identifier = 'foo';
        $objectClass = DummyTargetObjectClass::class;
        $settings = [
            'object' => [
                'class' => $objectClass,
            ],
        ];

        $dataTarget = $this->subject->get($settings, $identifier);
        self::assertInstanceOf(
            DataTargetFactory::DEFAULT_DATA_TARGET_CLASS,
            $dataTarget
        );
    }

    #[Test]
    public function testGetSetsIdentifierForIdentifiableTarget(): void
    {
        $identifier = 'foo';
        $dataTargetClass = DummyIdentifiableTargetInterfaceClass::class;
        $settings = [
            'class' => $dataTargetClass,
            'identifier' => 'barSourceIdentifier',
            'config' => [],
        ];

        $dataTarget = $this->subject->get($settings, $identifier);
        if ($dataTarget instanceof DummyIdentifiableTargetInterfaceClass) {
            self::assertSame(
                $settings['identifier'],
                $dataTarget->getIdentifier()
            );
        }

        if (! $dataTarget instanceof DummyIdentifiableTargetInterfaceClass) {
            $this->fail('Factory did not create an instance of the expected class');
        }
    }
}
