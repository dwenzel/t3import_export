<?php
namespace CPSIT\T3importExport\Tests\Unit\Persistence\Factory;

use CPSIT\T3importExport\ConfigurableTrait;
use CPSIT\T3importExport\IdentifiableInterface;
use CPSIT\T3importExport\IdentifiableTrait;
use CPSIT\T3importExport\Persistence\DataSourceInterface;
use CPSIT\T3importExport\Persistence\Factory\DataSourceFactory;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Extbase\Object\ObjectManager;

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
    public function getRecords(array $configuration)
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
    public function getRecords(array $configuration)
    {
        return [];
    }

    /**
     * Fake method matches abstract method in ConfigurableInterface
     *
     * @param array $configuration
     * @return bool
     */
    public function isConfigurationValid(array $configuration)
    {
        return true;
    }
}

/**
 * Class DummySourceInterfaceClass
 *
 * @package CPSIT\T3importExport\Tests\Unit\Persistence\Factory
 */
class DummySourceClass implements DataSourceInterface
{
    use ConfigurableTrait;

    /**
     * Fake method matches DataSourceInterface
     *
     * @param array $configuration
     * @return array
     */
    public function getRecords(array $configuration)
    {
        return [];
    }

    /**
     * Fake method matches abstract method in ConfigurableInterface
     *
     * @param array $configuration
     * @return bool
     */
    public function isConfigurationValid(array $configuration)
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
class DataSourceFactoryTest extends UnitTestCase
{

    /**
     * @var DataSourceFactory
     */
    protected $subject;

    /**
     * set up
     */
    public function setUp()
    {
        $this->subject = $this->getMock(
            DataSourceFactory::class, ['dummy']
        );
    }

    /**
     * @test
     * @expectedException \CPSIT\T3importExport\MissingClassException
     * @expectedExceptionCode 1451060913
     */
    public function getThrowsExceptionForMissingSourceClass()
    {
        $identifier = 'foo';
        $settings = [
            'class' => 'NonExistingSourceClass'
        ];
        $this->subject->get($settings, $identifier);
    }

    /**
     * @test
     * @expectedException \CPSIT\T3importExport\MissingInterfaceException
     * @expectedExceptionCode 1451061361
     */
    public function getThrowsExceptionForMissingDataSourceInterface()
    {
        $identifier = 'foo';
        $settings = [
            'class' => DummyMissingSourceInterfaceClass::class
        ];
        $this->subject->get($settings, $identifier);
    }

    /**
     * @test
     * @expectedException \CPSIT\T3importExport\InvalidConfigurationException
     * @expectedExceptionCode 1451086595
     */
    public function getThrowsExceptionForMissingConfig()
    {
        $identifier = 'foo';
        $dataSourceClass = DummySourceClass::class;
        $settings = [
            'class' => $dataSourceClass,
        ];

        $this->subject->get($settings, $identifier);
    }

    /**
     * @test
     */
    public function getSetsIdentifierForIdentifiableSource()
    {
        $identifier = 'foo';
        $dataSourceClass = DummyIdentifiableSourceInterfaceClass::class;
        $settings = [
            'class' => $dataSourceClass,
            'identifier' => 'barSourceIdentifier',
            'config' => []
        ];
        $mockDataSource = $this->getMock(
            $dataSourceClass,
            ['setIdentifier']
        );
        $mockDataSource->expects($this->once())
            ->method('setIdentifier')
            ->with($settings['identifier']);
        $mockObjectManager = $this->getMock(
            ObjectManager::class, ['get']
        );
        /** @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject $mockObjectManager */
        $mockObjectManager->expects($this->once())
            ->method('get')
            ->with($dataSourceClass)
            ->will($this->returnValue($mockDataSource));
        $this->subject->injectObjectManager($mockObjectManager);

        $this->subject->get($settings, $identifier);
    }

    /**
     * @test
     */
    public function getReturnsDefaultDataSource()
    {
        $identifier = 'foo';
        $dataSourceClass = DataSourceFactory::DEFAULT_DATA_SOURCE_CLASS;
        $expectedDataSource = $this->getMock(
            $dataSourceClass, [], [], '', false
        );
        $settings = [
            'identifier' => $identifier,
            'config' => []
        ];
        $mockObjectManager = $this->getMock(
            ObjectManager::class, ['get']
        );
        $mockObjectManager->expects($this->once())
            ->method('get')
            ->with($dataSourceClass)
            ->will($this->returnValue($expectedDataSource));
        $this->subject->injectObjectManager($mockObjectManager);
        $this->assertSame(
            $expectedDataSource,
            $this->subject->get($settings, $identifier)
        );
    }

    /**
     * @test
     */
    public function getReturnsDataSource()
    {
        $identifier = 'foo';
        $dataSourceClass = DummySourceClass::class;
        $expectedDataSource = $this->getMock(
            $dataSourceClass
        );
        $settings = [
            'class' => $dataSourceClass,
            'config' => []
        ];
        $mockObjectManager = $this->getMock(
            ObjectManager::class, ['get']
        );
        $mockObjectManager->expects($this->once())
            ->method('get')
            ->with($dataSourceClass)
            ->will($this->returnValue($expectedDataSource));
        $this->subject->injectObjectManager($mockObjectManager);
        $this->assertSame(
            $expectedDataSource,
            $this->subject->get($settings, $identifier)
        );
    }

    /**
     * @test
     */
    public function getSetsConfiguration()
    {
        $identifier = 'foo';
        $dataSourceClass = DummySourceClass::class;
        $mockDataSource = $this->getMock(
            $dataSourceClass
        );
        $settings = [
            'class' => $dataSourceClass,
            'config' => []
        ];
        $mockDataSource->expects($this->once())
            ->method('setConfiguration')
            ->with($settings['config']);
        $mockObjectManager = $this->getMock(
            ObjectManager::class, ['get']
        );
        $mockObjectManager->expects($this->once())
            ->method('get')
            ->with($dataSourceClass)
            ->will($this->returnValue($mockDataSource));
        $this->subject->injectObjectManager($mockObjectManager);

        $this->subject->get($settings, $identifier);
    }
}
