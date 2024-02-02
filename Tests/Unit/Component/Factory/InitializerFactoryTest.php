<?php
namespace CPSIT\T3importExport\Tests\Unit\Component\Factory;

use CPSIT\T3importExport\Component\Initializer\AbstractInitializer;
use CPSIT\T3importExport\Component\Initializer\InitializerInterface;
use CPSIT\T3importExport\Component\Factory\InitializerFactory;
use CPSIT\T3importExport\InvalidConfigurationException;
use CPSIT\T3importExport\Tests\Unit\Traits\MockObjectManagerTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;

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
 * Class DummyInvalidInitializer
 * Does not implement InitializerInterface
 *
 * @package CPSIT\T3importExport\Tests\Component\Factory
 */
class DummyInvalidInitializer
{
}

/**
 * Class DummyValidInitializer
 *
 * @package CPSIT\T3importExport\Tests\Unit\Component\Factory
 */
class DummyValidInitializer extends AbstractInitializer implements InitializerInterface
{
    /**
     * @param array $configuration
     * @param array $records
     * @return bool
     */
    public function process(array $configuration, array &$records): bool
    {
        return true;
    }
}

/**
 * Class InitializerFactoryTest
 *
 * @package CPSIT\T3importExport\Tests\Unit\Component\Factory
 */
class InitializerFactoryTest extends TestCase
{
    use MockObjectManagerTrait;

    /**
     * @var InitializerFactory
     */
    protected $subject;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->subject = new InitializerFactory();
    }

    public function testGetThrowsInvalidConfigurationExceptionIfClassIsNotSet(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionCode(1_454_588_350);
        $configurationWithoutClassName = ['bar'];

        $this->subject->get($configurationWithoutClassName, 'fooIdentifier');
    }

    public function testGetThrowsInvalidConfigurationExceptionIfClassDoesNotExist(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionCode(1_454_588_360);
        $configurationWithNonExistingClass = [
            'class' => 'NonExistingClass'
        ];
        $this->subject->get(
            $configurationWithNonExistingClass
        );
    }

    public function testGetThrowsExceptionIfClassDoesNotImplementInitializerInterface(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionCode(1_454_588_370);

        $configurationWithExistingClass = [
            'class' => DummyInvalidInitializer::class
        ];
        $this->subject->get(
            $configurationWithExistingClass
        );
    }

    /**
     * @test
     */
    public function getReturnsInitializer(): void
    {
        $identifier = 'fooIdentifier';
        $validClass = DummyValidInitializer::class;
        $settings = [
            'class' => $validClass,
        ];

        $this->assertInstanceOf(
            $validClass,
            $this->subject->get($settings, $identifier)
        );
    }
}
