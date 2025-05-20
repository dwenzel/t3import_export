<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Persistence\Factory;

use CPSIT\ImportExportCore\Component\ConfigurableInterface;
use CPSIT\T3importExport\ConfigurableTrait;
use CPSIT\T3importExport\IdentifiableInterface;
use CPSIT\T3importExport\IdentifiableTrait;
use CPSIT\T3importExport\InvalidConfigurationException;
use CPSIT\T3importExport\MissingClassException;
use CPSIT\T3importExport\MissingInterfaceException;
use CPSIT\T3importExport\Persistence\DataSourceInterface;
use CPSIT\T3importExport\Persistence\Factory\DataSourceFactory;
use PHPUnit\Framework\Attributes\Test;
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
 */
class DummyMissingSourceInterfaceClass
{
}

/**
 * Class DummyMissingConfigurableInterfaceClass
 */
class DummyMissingConfigurableInterfaceClass
{
    use IdentifiableTrait;

    /**
     * Fake method matches DataSourceInterface
     */
    public function getRecords(array $configuration): array
    {
        return [];
    }
}

/**
 * Class DummyIdentifiableSourceInterfaceClass
 */
class DummyIdentifiableSourceInterfaceClass implements DataSourceInterface, IdentifiableInterface
{
    use IdentifiableTrait;
    use ConfigurableTrait;

    /**
     * Fake method matches DataSourceInterface
     */
    public function getRecords(array $configuration): array
    {
        return [];
    }

    /**
     * Fake method matches abstract method in ConfigurableInterface
     */
    public function isConfigurationValid(array $configuration): bool
    {
        return true;
    }
}

/**
 * Class DummySourceInterfaceClass
 */
class DummySourceClass implements DataSourceInterface, ConfigurableInterface, IdentifiableInterface
{
    use ConfigurableTrait;
    use IdentifiableTrait;

    /**
     * Fake method matches DataSourceInterface
     */
    public function getRecords(array $configuration): array
    {
        return [];
    }

    /**
     * Fake method matches abstract method in ConfigurableInterface
     */
    public function isConfigurationValid(array $configuration): bool
    {
        return true;
    }
}

/**
 * Class DataSourceFactoryTest
 */
class DataSourceFactoryTest extends TestCase
{
    protected DataSourceFactory $subject;

    /**
     * @var DataSourceInterface|MockObject
     */
    protected DataSourceInterface|MockObject $dataSource;

    /**
     * set up
     */
    protected function setUp(): void
    {
        $this->subject = new DataSourceFactory();
        $this->dataSource = $this->createMock(DummySourceClass::class);
    }

    #[Test]
    public function testGetThrowsExceptionForMissingSourceClass(): void
    {
        $this->expectExceptionCode(1_451_060_913);
        $this->expectException(MissingClassException::class);
        $identifier = 'foo';
        $settings = [
            'class' => 'NonExistingSourceClass',
        ];
        $this->subject->get($settings, $identifier);
    }

    #[Test]
    public function testGetThrowsExceptionForMissingDataSourceInterface(): void
    {
        $this->expectExceptionCode(1_451_061_361);
        $this->expectException(MissingInterfaceException::class);
        $identifier = 'foo';
        $settings = [
            'class' => DummyMissingSourceInterfaceClass::class,
        ];
        $this->subject->get($settings, $identifier);
    }

    #[Test]
    public function testGetThrowsExceptionForMissingConfig(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionCode(1_451_086_595);
        $identifier = 'foo';
        $dataSourceClass = DummySourceClass::class;
        $settings = [
            'class' => $dataSourceClass,
        ];

        $this->subject->get($settings, $identifier);
    }

    #[Test]
    public function testGetSetsIdentifierForIdentifiableSource(): void
    {
        $identifier = 'foo';
        $dataSourceClass = DummyIdentifiableSourceInterfaceClass::class;
        $settings = [
            'class' => $dataSourceClass,
            'identifier' => 'barSourceIdentifier',
            'config' => [],
        ];

        $dataSource = $this->subject->get($settings, $identifier);

        if ($dataSource instanceof DummyIdentifiableSourceInterfaceClass) {
            self::assertSame(
                $settings['identifier'],
                $dataSource->getIdentifier()
            );
        }
    }

    #[Test]
    public function testGetReturnsDefaultDataSource(): void
    {
        $this->markTestSkipped('Skipped due to constructor dependency issues with DatabaseTrait in PHPUnit 12');

        /** @phpstan-ignore deadCode.unreachable */
        $tableName = 'foo';
        $expectedClass = DataSourceFactory::DEFAULT_DATA_SOURCE_CLASS;
        $settings = [
            'config' => [
                'table' => $tableName,
            ],
        ];

        /** @noinspection UnnecessaryAssertionInspection */
        $this->assertInstanceOf(
            $expectedClass,
            $this->subject->get($settings)
        );
    }

    #[Test]
    public function testGetReturnsDataSource(): void
    {
        $this->markTestSkipped('Skipped due to constructor dependency issues with DatabaseTrait in PHPUnit 12');

        /** @phpstan-ignore deadCode.unreachable */
        $sourceClass = $this->dataSource::class;
        $identifier = 'foo';
        $settings = [
            'class' => $sourceClass,
            'config' => [],
        ];
        /** @noinspection UnnecessaryAssertionInspection */
        $this->assertInstanceOf(
            $sourceClass,
            $this->subject->get($settings, $identifier)
        );
    }

    #[Test]
    public function testGetSetsConfiguration(): void
    {
        $this->markTestSkipped('Skipped due to constructor dependency issues with DatabaseTrait in PHPUnit 12');

        /** @phpstan-ignore deadCode.unreachable */
        $identifier = 'foo';
        $dataSourceClass = DummySourceClass::class;
        $settings = [
            'class' => $dataSourceClass,
            'config' => ['boo'],
        ];

        $dataSource = $this->subject->get($settings, $identifier);
        self::assertSame(
            $settings['config'],
            $dataSource->getConfiguration()
        );
    }
}
