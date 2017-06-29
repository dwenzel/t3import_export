<?php
namespace CPSIT\T3importExport\Tests\Unit\Component\Factory;

use CPSIT\T3importExport\Component\Converter\AbstractConverter;
use CPSIT\T3importExport\Component\Converter\ConverterInterface;
use CPSIT\T3importExport\Component\Factory\ConverterFactory;
use CPSIT\T3importExport\Component\Factory\PreProcessorFactory;
use CPSIT\T3importExport\Component\PreProcessor\AbstractPreProcessor;
use CPSIT\T3importExport\Component\PreProcessor\PreProcessorInterface;
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
 * Class DummyInvalidConverter
 * Does not implement ConverterInterface
 *
 * @package CPSIT\T3importExport\Tests\Component\Factory
 */
class DummyInvalidConverter
{
}

/**
 * Class DummyValidConverter
 *
 * @package CPSIT\T3importExport\Tests\Unit\Component\Factory
 */
class DummyValidConverter extends AbstractConverter implements ConverterInterface
{
    /**
     * @param array $configuration
     * @param array $record
     * @return bool
     */
    public function convert(array $record, array $configuration)
    {
        return true;
    }
}

/**
 * Class ConverterFactoryTest
 *
 * @package CPSIT\T3importExport\Tests\Unit\Component\Factory
 */
class ConverterFactoryTest extends UnitTestCase
{

    /**
     * @var \CPSIT\T3importExport\Component\Factory\ConverterFactory
     */
    protected $subject;

    /**
     *
     */
    public function setUp()
    {
        $this->subject = $this->getAccessibleMock(
            ConverterFactory::class,
            ['dummy']
        );
    }

    /**
     * @test
     * @expectedException \CPSIT\T3importExport\InvalidConfigurationException
     * @expectedExceptionCode 1451566686
     */
    public function getThrowsInvalidConfigurationExceptionIfClassIsNotSet()
    {
        $configurationWithoutClassName = ['bar'];

        $this->subject->get($configurationWithoutClassName, 'fooIdentifier');
    }

    /**
     * @test
     * @expectedException \CPSIT\T3importExport\InvalidConfigurationException
     * @expectedExceptionCode 1451566699
     */
    public function getThrowsInvalidConfigurationExceptionIfClassDoesNotExist()
    {
        $configurationWithNonExistingClass = [
            'class' => 'NonExistingClass'
        ];
        $this->subject->get(
            $configurationWithNonExistingClass
        );
    }

    /**
     * @test
     * @expectedException \CPSIT\T3importExport\InvalidConfigurationException
     * @expectedExceptionCode 1451566706
     */
    public function getThrowsExceptionIfClassDoesNotImplementConverterInterface()
    {
        $configurationWithExistingClass = [
            'class' => DummyInvalidConverter::class
        ];
        $this->subject->get(
            $configurationWithExistingClass
        );
    }

    /**
     * @test
     */
    public function getReturnsConverter()
    {
        $identifier = 'fooIdentifier';
        $validClass = DummyValidConverter::class;
        $settings = [
            'class' => $validClass,
        ];
        $mockObjectManager = $this->getMock(
            ObjectManager::class, ['get']
        );
        $this->subject->injectObjectManager($mockObjectManager);
        $mockConverter = $this->getMock($validClass);
        $mockObjectManager->expects($this->once())
            ->method('get')
            ->with($validClass)
            ->will($this->returnValue($mockConverter));
        $this->assertEquals(
            $mockConverter,
            $this->subject->get($settings, $identifier)
        );
    }
}
