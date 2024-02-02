<?php

namespace CPSIT\T3importExport\Tests\Unit\Component\Factory;

use CPSIT\T3importExport\Component\Factory\PostProcessorFactory;
use CPSIT\T3importExport\Component\PostProcessor\AbstractPostProcessor;
use CPSIT\T3importExport\Component\PostProcessor\PostProcessorInterface;
use CPSIT\T3importExport\InvalidConfigurationException;
use CPSIT\T3importExport\Tests\Unit\Traits\MockObjectManagerTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class DummyValidPostProcessor
 *
 * @package CPSIT\T3importExport\Tests\Unit\Component\Factory
 */
class DummyValidPostProcessor extends AbstractPostProcessor implements PostProcessorInterface
{
    /**
     * processes the converted record
     *
     * @param array $configuration
     * @param mixed $convertedRecord
     * @param array $record
     * @return bool
     */
    public function process(array $configuration, &$convertedRecord, array &$record): bool
    {
        return true;
    }
}

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
 * Class DummyInvalidPostProcessor
 * Does not implement PostProcessorInterface
 *
 * @package CPSIT\T3importExport\Tests\Component\Factory
 */
class DummyInvalidPostProcessor
{
}

/**
 * Class PostProcessorFactoryTest
 */
class PostProcessorFactoryTest extends TestCase
{
    protected PostProcessorFactory $subject;

    /** @noinspection ReturnTypeCanBeDeclaredInspection */
    protected function setUp(): void
    {
        $this->subject = new PostProcessorFactory();
    }

    public function testGetThrowsInvalidConfigurationExceptionIfClassIsNotSet(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionCode(1_447_864_207);
        $configurationWithoutClassName = ['bar'];

        $this->subject->get($configurationWithoutClassName, 'fooIdentifier');
    }

    public function testGetThrowsInvalidConfigurationExceptionIfClassDoesNotExist(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionCode(1_447_864_223);
        $configurationWithNonExistingClass = [
            'class' => 'NonExistingClass'
        ];
        $this->subject->get(
            $configurationWithNonExistingClass
        );
    }

    public function testGetThrowsExceptionIfClassDoesNotImplementPostProcessorInterface(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionCode(1_447_864_243);
        $configurationWithExistingClass = [
            'class' => DummyInvalidPostProcessor::class
        ];
        $this->subject->get(
            $configurationWithExistingClass
        );
    }

    public function testGetReturnsPostProcessor(): void
    {
        $identifier = 'fooIdentifier';
        $validClass = DummyValidPostProcessor::class;
        $settings = [
            'class' => $validClass,
        ];
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->assertInstanceOf(
            $validClass,
            $this->subject->get($settings, $identifier)
        );
    }
}
