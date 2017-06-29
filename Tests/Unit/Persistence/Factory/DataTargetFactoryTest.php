<?php
namespace CPSIT\T3importExport\Tests\Unit\Persistence\Factory;

use CPSIT\T3importExport\ConfigurableTrait;
use CPSIT\T3importExport\IdentifiableInterface;
use CPSIT\T3importExport\IdentifiableTrait;
use CPSIT\T3importExport\Persistence\DataTargetInterface;
use CPSIT\T3importExport\Persistence\Factory\DataTargetFactory;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
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
    public function getRecords(array $configuration)
    {
        return [];
    }

    /**
     * Fake method matches abstract method in DataTargetInterface
     *
     * @param array|DomainObjectInterface $object
     * @param array $configuration
     * @return bool
     */
    public function isConfigurationValid(array $configuration)
    {
        return true;
    }

    /**
     * Fake method matches abstract method in ConfigurableInterface
     *
     * @param array $configuration
     * @return bool
     */
    public function persist($object, array $configuration = null)
    {
    }
    /**
     * Dummy method
     * Currently does'nt do anything
     *
     * @param null $result
     * @param array|null $configuration
     * @return void
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
class DataTargetFactoryTest extends UnitTestCase
{

    /**
     * @var DataTargetFactory
     */
    protected $subject;

    /**
     * set up
     */
    public function setUp()
    {
        $this->subject = $this->getMock(
            DataTargetFactory::class, ['dummy']
        );
    }

    /**
     * @test
     * @expectedException \CPSIT\T3importExport\MissingClassException
     * @expectedExceptionCode 1451043513
     */
    public function getThrowsExceptionForMissingTargetClass()
    {
        $identifier = 'foo';
        $settings = [
            'class' => 'NonExistingTargetClass'
        ];
        $this->subject->get($settings, $identifier);
    }

    /**
     * @test
     * @expectedException \CPSIT\T3importExport\MissingInterfaceException
     * @expectedExceptionCode 1451045997
     */
    public function getThrowsExceptionForMissingInterface()
    {
        $identifier = 'foo';
        $settings = [
            'class' => DummyMissingTargetInterfaceClass::class
        ];
        $this->subject->get($settings, $identifier);
    }

    /**
     * @test
     * @expectedException \CPSIT\T3importExport\MissingClassException
     * @expectedExceptionCode 1451043367
     */
    public function getThrowsExceptionForMissingObjectClass()
    {
        $identifier = 'foo';
        $settings = [
            'object' => [
                'class' => 'NonExistingObjectClass'
            ]
        ];
        $this->subject->get($settings, $identifier);
    }

    /**
     * @test
     */
    public function getReturnsDefaultDataTarget()
    {
        $identifier = 'foo';
        $objectClass = DummyTargetObjectClass::class;
        $dataTargetClass = DataTargetFactory::DEFAULT_DATA_TARGET_CLASS;
        $expectedDataTarget = $this->getMock(
            $dataTargetClass,
            [], [$objectClass]
        );
        $mockObjectManager = $this->getMock(
            ObjectManager::class, ['get']
        );
        $mockObjectManager->expects($this->once())
            ->method('get')
            ->with($dataTargetClass, $objectClass);
        $this->subject->injectObjectManager($mockObjectManager);
        $settings = [
            'object' => [
                'class' => $objectClass
            ]
        ];
        $this->subject->get($settings, $identifier);
    }

    /**
     * @test
     */
    public function getSetsIdentifierForIdentifiableTarget()
    {
        $identifier = 'foo';
        $dataSourceClass = DummyIdentifiableTargetInterfaceClass::class;
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
        /** @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject $mockObjectManager */
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
