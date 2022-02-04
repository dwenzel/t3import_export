<?php

namespace CPSIT\T3importExport\Tests\Unit\Component\Factory;

use CPSIT\T3importExport\Component\Converter\AbstractConverter;
use CPSIT\T3importExport\Component\Converter\ConverterInterface;
use CPSIT\T3importExport\Component\Factory\ConverterFactory;
use CPSIT\T3importExport\InvalidConfigurationException;
use PHPUnit\Framework\TestCase;
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
    public function convert(array $record, array $configuration): bool
    {
        return true;
    }
}

/**
 * Class ConverterFactoryTest
 *
 * @package CPSIT\T3importExport\Tests\Unit\Component\Factory
 */
class ConverterFactoryTest extends TestCase
{

    /**
     * @var ConverterFactory
     */
    protected ConverterFactory $subject;

    /**
     *
     * @noinspection ReturnTypeCanBeDeclaredInspection
     */
    public function setUp()
    {
        $this->subject = new ConverterFactory();
    }

    public function testGetThrowsInvalidConfigurationExceptionIfClassIsNotSet(): void
    {
        $configurationWithoutClassName = ['bar'];

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionCode(1451566686);
        $this->subject->get($configurationWithoutClassName, 'fooIdentifier');
    }

    public function testGetThrowsInvalidConfigurationExceptionIfClassDoesNotExist(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionCode(1451566699);
        $configurationWithNonExistingClass = [
            'class' => 'NonExistingClass'
        ];
        $this->subject->get(
            $configurationWithNonExistingClass
        );
    }

    public function testGetThrowsExceptionIfClassDoesNotImplementConverterInterface(): void
    {
        $configurationWithExistingClass = [
            'class' => DummyInvalidConverter::class
        ];
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionCode(1451566706);
        $this->subject->get(
            $configurationWithExistingClass
        );
    }

    public function testGetReturnsConverter(): void
    {
        $identifier = 'fooIdentifier';
        $validClass = DummyValidConverter::class;
        $settings = [
            'class' => $validClass,
        ];
        $converter = new $validClass;
        $this->assertInstanceOf(
            get_class($converter),
            $this->subject->get($settings, $identifier)
        );
    }
}
