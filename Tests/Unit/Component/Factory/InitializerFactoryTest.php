<?php
namespace CPSIT\T3import\Tests\Unit\Component\Factory;

use CPSIT\T3import\Component\Initializer\AbstractInitializer;
use CPSIT\T3import\Component\Initializer\InitializerInterface;
use CPSIT\T3import\Component\Factory\InitializerFactory;
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
 * Class DummyInvalidInitializer
 * Does not implement InitializerInterface
 *
 * @package CPSIT\T3import\Tests\Component\Factory
 */
class DummyInvalidInitializer {
}

/**
 * Class DummyValidInitializer
 *
 * @package CPSIT\T3import\Tests\Unit\Component\Factory
 */
class DummyValidInitializer
	extends AbstractInitializer
	implements InitializerInterface {
	/**
	 * @param array $configuration
	 * @param array $records
	 * @return bool
	 */
	public function process($configuration, &$records) {
		return true;
	}
}

/**
 * Class InitializerFactoryTest
 *
 * @package CPSIT\T3import\Tests\Unit\Component\Factory
 */
class InitializerFactoryTest extends UnitTestCase {

	/**
	 * @var \CPSIT\T3import\Component\Factory\InitializerFactory
	 */
	protected $subject;

	/**
	 *
	 */
	public function setUp() {
		$this->subject = $this->getAccessibleMock(
			InitializerFactory::class,
			['dummy']
		);
	}

	/**
	 * @test
	 * @expectedException \CPSIT\T3import\InvalidConfigurationException
	 * @expectedExceptionCode 1454588350
	 */
	public function getThrowsInvalidConfigurationExceptionIfClassIsNotSet() {
		$configurationWithoutClassName = ['bar'];

		$this->subject->get($configurationWithoutClassName, 'fooIdentifier');
	}

	/**
	 * @test
	 * @expectedException \CPSIT\T3import\InvalidConfigurationException
	 * @expectedExceptionCode 1454588360
	 */
	public function getThrowsInvalidConfigurationExceptionIfClassDoesNotExist() {
		$configurationWithNonExistingClass = [
			'class' => 'NonExistingClass'
		];
		$this->subject->get(
			$configurationWithNonExistingClass
		);
	}

	/**
	 * @test
	 * @expectedException \CPSIT\T3import\InvalidConfigurationException
	 * @expectedExceptionCode 1454588370
	 */
	public function getThrowsExceptionIfClassDoesNotImplementInitializerInterface() {
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
	public function getReturnsInitializer() {
		$identifier = 'fooIdentifier';
		$validClass = DummyValidInitializer::class;
		$settings = [
			'class' => $validClass,
		];
		$mockObjectManager = $this->getMock(
			ObjectManager::class, ['get']
		);
		$this->subject->injectObjectManager($mockObjectManager);
		$mockInitializer = $this->getMock($validClass);
		$mockObjectManager->expects($this->once())
			->method('get')
			->with($validClass)
			->will($this->returnValue($mockInitializer));
		$this->assertEquals(
			$mockInitializer,
			$this->subject->get($settings, $identifier)
		);
	}
}