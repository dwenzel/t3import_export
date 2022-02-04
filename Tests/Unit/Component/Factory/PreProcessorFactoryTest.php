<?php

namespace CPSIT\T3importExport\Tests\Unit\Component\Factory;

use CPSIT\T3importExport\Component\Factory\PreProcessorFactory;
use CPSIT\T3importExport\Component\PreProcessor\AbstractPreProcessor;
use CPSIT\T3importExport\Component\PreProcessor\PreProcessorInterface;
use CPSIT\T3importExport\InvalidConfigurationException;
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
 * Class DummyInvalidPreProcessor
 * Does not implement PreProcessorInterface
 *
 * @package CPSIT\T3importExport\Tests\Component\Factory
 */
class DummyInvalidPreProcessor
{
}

/**
 * Class DummyValidPreProcessor
 *
 * @package CPSIT\T3importExport\Tests\Unit\Component\Factory
 */
class DummyValidPreProcessor extends AbstractPreProcessor implements PreProcessorInterface
{
    /**
     * @param array $configuration
     * @param array $record
     * @return bool
     * @noinspection PhpMissingParamTypeInspection
     */
    public function process($configuration, &$record): bool
    {
        return true;
    }
}

/**
 * Class PreProcessorFactoryTest
 *
 * @package CPSIT\T3importExport\Tests\Unit\Component\Factory
 */
class PreProcessorFactoryTest extends TestCase
{
    protected PreProcessorFactory $subject;

    /**
     *
     * @noinspection ReturnTypeCanBeDeclaredInspection
     */
    public function setUp()
    {
        $this->subject = new PreProcessorFactory();
    }

    public function testGetThrowsInvalidConfigurationExceptionIfClassIsNotSet(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionCode(1447427020);

        $configurationWithoutClassName = ['bar'];

        $this->subject->get($configurationWithoutClassName, 'fooIdentifier');
    }

    public function testGetThrowsInvalidConfigurationExceptionIfClassDoesNotExist(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionCode(1447427184);
        $configurationWithNonExistingClass = [
            'class' => 'NonExistingClass'
        ];
        $this->subject->get(
            $configurationWithNonExistingClass
        );
    }

    public function testGetThrowsExceptionIfClassDoesNotImplementPreProcessorInterface(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionCode(1447428235);
        $configurationWithExistingClass = [
            'class' => DummyInvalidPreProcessor::class
        ];
        $this->subject->get(
            $configurationWithExistingClass
        );
    }

    /**
     * @throws InvalidConfigurationException
     */
    public function testGetReturnsPreProcessor(): void
    {
        $identifier = 'fooIdentifier';
        $validClass = DummyValidPreProcessor::class;
        $settings = [
            'class' => $validClass,
        ];
        $this->assertInstanceOf(
            $validClass,
            $this->subject->get($settings, $identifier)
        );
    }
}
