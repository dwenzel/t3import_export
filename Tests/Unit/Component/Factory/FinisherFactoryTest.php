<?php

namespace CPSIT\T3importExport\Tests\Unit\Component\Factory;

use CPSIT\T3importExport\Component\Factory\FinisherFactory;
use CPSIT\T3importExport\Component\Finisher\AbstractFinisher;
use CPSIT\T3importExport\Component\Finisher\FinisherInterface;
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
 * Class DummyInvalidFinisher
 * Does not implement FinisherInterface
 *
 * @package CPSIT\T3importExport\Tests\Component\Factory
 */
class DummyInvalidFinisher
{
}

/**
 * Class DummyValidFinisher
 *
 * @package CPSIT\T3importExport\Tests\Unit\Component\Factory
 */
class DummyValidFinisher extends AbstractFinisher implements FinisherInterface
{
    /**
     * @param array $configuration
     * @param array $records
     * @param array $result
     * @return bool
     */
    public function process(array $configuration, array &$records, &$result): bool
    {
        return true;
    }
}

/**
 * Class FinisherFactoryTest
 *
 * @package CPSIT\T3importExport\Tests\Unit\Component\Factory
 */
class FinisherFactoryTest extends TestCase
{

    /**
     * @var FinisherFactory
     */
    protected FinisherFactory $subject;

    /** @noinspection ReturnTypeCanBeDeclaredInspection */
    protected function setUp(): void
    {
        $this->subject = new FinisherFactory();
    }

    public function testGetThrowsInvalidConfigurationExceptionIfClassIsNotSet(): void
    {
        $configurationWithoutClassName = ['bar'];
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionCode(1_454_187_892);
        $this->subject->get($configurationWithoutClassName, 'fooIdentifier');
    }

    public function testGetThrowsInvalidConfigurationExceptionIfClassDoesNotExist(): void
    {
        $configurationWithNonExistingClass = [
            'class' => 'NonExistingClass'
        ];
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionCode(1_454_187_903);
        $this->subject->get(
            $configurationWithNonExistingClass
        );
    }

    public function testGetThrowsExceptionIfClassDoesNotImplementFinisherInterface(): void
    {
        $configurationWithExistingClass = [
            'class' => DummyInvalidFinisher::class
        ];
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionCode(1_454_187_910);
        $this->subject->get(
            $configurationWithExistingClass
        );
    }

    public function testGetReturnsFinisher(): void
    {
        $identifier = 'fooIdentifier';
        $validClass = DummyValidFinisher::class;
        $settings = [
            'class' => $validClass,
        ];
        $this->assertInstanceOf(
            $validClass,
            $this->subject->get($settings, $identifier)
        );
    }
}
