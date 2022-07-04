<?php

namespace CPSIT\T3importExport\Tests\Unit\Persistence\Factory;

use CPSIT\T3importExport\ConfigurableInterface;
use CPSIT\T3importExport\ConfigurableTrait;
use CPSIT\T3importExport\IdentifiableInterface;
use CPSIT\T3importExport\IdentifiableTrait;
use CPSIT\T3importExport\InvalidConfigurationException;
use CPSIT\T3importExport\MissingClassException;
use CPSIT\T3importExport\MissingInterfaceException;
use CPSIT\T3importExport\Persistence\DataSourceInterface;
use CPSIT\T3importExport\Persistence\Factory\DataSourceFactory;
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
 * Class DummyMissingSourceInterface
 *
 * @package CPSIT\T3importExport\Tests\Unit\Persistence\Factory
 */
class DummyMissingSourceInterfaceClass
{
}

/**
 * Class DummyMissingConfigurableInterfaceClass
 *
 * @package CPSIT\T3importExport\Tests\Unit\Persistence\Factory
 */
class DummyMissingConfigurableInterfaceClass
{
    use IdentifiableTrait;

    /**
     * Fake method matches DataSourceInterface
     *
     * @param array $configuration
     * @return array
     */
    public function getRecords(array $configuration): array
    {
        return [];
    }
}

/**
 * Class DummyIdentifiableSourceInterfaceClass
 *
 * @package CPSIT\T3importExport\Tests\Unit\Persistence\Factory
 */
class DummyIdentifiableSourceInterfaceClass implements DataSourceInterface, IdentifiableInterface
{
    use IdentifiableTrait, ConfigurableTrait;

    /**
     * Fake method matches DataSourceInterface
     *
     * @param array $configuration
     * @return array
     */
    public function getRecords(array $configuration): array
    {
        return [];
    }

    /**
     * Fake method matches abstract method in ConfigurableInterface
     *
     * @param array $configuration
     * @return bool
     */
    public function isConfigurationValid(array $configuration): bool
    {
        return true;
    }
}

/**
 * Class DummySourceInterfaceClass
 *
 * @package CPSIT\T3importExport\Tests\Unit\Persistence\Factory
 */
class DummySourceClass implements DataSourceInterface, ConfigurableInterface
{
    use ConfigurableTrait;

    /**
     * Fake method matches DataSourceInterface
     *
     * @param array $configuration
     * @return array
     */
    public function getRecords(array $configuration): array
    {
        return [];
    }

    /**
     * Fake method matches abstract method in ConfigurableInterface
     *
     * @param array $configuration
     * @return bool
     */
    public function isConfigurationValid(array $configuration): bool
    {
        return true;
    }
}

/**
 * Class DataSourceFactoryTest
 *
 * @package CPSIT\T3importExport\Tests\Unit\Persistence\Factory
 * @coversDefaultClass \CPSIT\T3importExport\Persistence\Factory\DataSourceFactory
 */
class DataSourceFactoryTest extends TestCase
{
    protected DataSourceFactory $subject;

    /**
     * @var DataSourceInterface|MockObject
     */
    protected DataSourceInterface $dataSource;

    /**
     * set up
     * @noinspection ReturnTypeCanBeDeclaredInspection
     */
    public function setUp()
    {
        $this->subject = new DataSourceFactory();
        $this->dataSource = $this->getMockBuilder(DummySourceClass::class)
            ->setMethods(['setIdentifier'])
            ->getMock();
    }

    /**
     * @throws InvalidConfigurationException
     * @throws MissingClassException
     * @throws MissingInterfaceException
     */
    public function testGetThrowsExceptionForMissingSourceClass(): void
    {
        $this->expectExceptionCode(1451060913);
        $this->expectException(MissingClassException::class);
        $identifier = 'foo';
        $settings = [
            'class' => 'NonExistingSourceClass'
        ];
        $this->subject->get($settings, $identifier);
    }

    /**
     * @throws InvalidConfigurationException
     * @throws MissingClassException
     * @throws MissingInterfaceException
     */
    public function testGetThrowsExceptionForMissingDataSourceInterface(): void
    {
        $this->expectExceptionCode(1451061361);
        $this->expectException(MissingInterfaceException::class);
        $identifier = 'foo';
        $settings = [
            'class' => DummyMissingSourceInterfaceClass::class
        ];
        $this->subject->get($settings, $identifier);
    }

    /**
     * @throws InvalidConfigurationException
     * @throws MissingClassException
     * @throws MissingInterfaceException
     */
    public function testGetThrowsExceptionForMissingConfig(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionCode(1451086595);
        $identifier = 'foo';
        $dataSourceClass = DummySourceClass::class;
        $settings = [
            'class' => $dataSourceClass,
        ];

        $this->subject->get($settings, $identifier);
    }

    /**
     * @throws InvalidConfigurationException
     * @throws MissingClassException
     * @throws MissingInterfaceException
     */
    public function testGetSetsIdentifierForIdentifiableSource(): void
    {
        $identifier = 'foo';
        $dataSourceClass = DummyIdentifiableSourceInterfaceClass::class;
        $settings = [
            'class' => $dataSourceClass,
            'identifier' => 'barSourceIdentifier',
            'config' => []
        ];

        $dataSource = $this->subject->get($settings, $identifier);
        /** @noinspection UnnecessaryAssertionInspection */
        self::assertInstanceOf(
            $dataSourceClass,
            $dataSource
        );

        if ($dataSource instanceof DummyIdentifiableSourceInterfaceClass) {
            self::assertSame(
                $settings['identifier'],
                $dataSource->getIdentifier()
            );
        }
    }

    /**
     * @throws InvalidConfigurationException
     * @throws MissingClassException
     * @throws MissingInterfaceException
     */
    public function testGetReturnsDefaultDataSource(): void
    {
        $tableName = 'foo';
        $expectedClass = DataSourceFactory::DEFAULT_DATA_SOURCE_CLASS;
        $settings = [
            'config' => [
                'table' => $tableName,
            ]
        ];

        /** @noinspection UnnecessaryAssertionInspection */
        $this->assertInstanceOf(
            $expectedClass,
            $this->subject->get($settings)
        );
    }

    /**
     * @throws InvalidConfigurationException
     * @throws MissingClassException
     * @throws MissingInterfaceException
     */
    public function testGetReturnsDataSource(): void
    {
        $sourceClass = get_class($this->dataSource);
        $identifier = 'foo';
        $settings = [
            'class' => $sourceClass,
            'config' => []
        ];
        /** @noinspection UnnecessaryAssertionInspection */
        $this->assertInstanceOf(
            $sourceClass,
            $this->subject->get($settings, $identifier)
        );
    }

    /**
     * @throws InvalidConfigurationException
     * @throws MissingClassException
     * @throws MissingInterfaceException
     */
    public function testGetSetsConfiguration(): void
    {
        $identifier = 'foo';
        $dataSourceClass = DummySourceClass::class;
        $settings = [
            'class' => $dataSourceClass,
            'config' => ['boo']
        ];

        $dataSource = $this->subject->get($settings, $identifier);
        self::assertSame(
            $settings['config'],
            $dataSource->getConfiguration()
        );
    }
}
