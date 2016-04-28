<?php
namespace CPSIT\T3importExport\Tests\Unit\Component\Factory;

use CPSIT\T3importExport\Component\Finisher\AbstractFinisher;
use CPSIT\T3importExport\Component\Finisher\FinisherInterface;
use CPSIT\T3importExport\Component\Factory\FinisherFactory;
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
 * Class DummyInvalidFinisher
 * Does not implement FinisherInterface
 *
 * @package CPSIT\T3importExport\Tests\Component\Factory
 */
class DummyInvalidFinisher {
}

/**
 * Class DummyValidFinisher
 *
 * @package CPSIT\T3importExport\Tests\Unit\Component\Factory
 */
class DummyValidFinisher
	extends AbstractFinisher
	implements FinisherInterface {
	/**
	 * @param array $configuration
	 * @param array $records
	 * @param array $result
	 * @return bool
	 */
	public function process($configuration, &$records, &$result) {
		return true;
	}
}

/**
 * Class FinisherFactoryTest
 *
 * @package CPSIT\T3importExport\Tests\Unit\Component\Factory
 */
class FinisherFactoryTest extends UnitTestCase {

	/**
	 * @var \CPSIT\T3importExport\Component\Factory\FinisherFactory
	 */
	protected $subject;

	/**
	 *
	 */
	public function setUp() {
		$this->subject = $this->getAccessibleMock(
			FinisherFactory::class,
			['dummy']
		);
	}

	/**
	 * @test
	 * @expectedException \CPSIT\T3importExport\InvalidConfigurationException
	 * @expectedExceptionCode 1454187892
	 */
	public function getThrowsInvalidConfigurationExceptionIfClassIsNotSet() {
		$configurationWithoutClassName = ['bar'];

		$this->subject->get($configurationWithoutClassName, 'fooIdentifier');
	}

	/**
	 * @test
	 * @expectedException \CPSIT\T3importExport\InvalidConfigurationException
	 * @expectedExceptionCode 1454187903
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
	 * @expectedException \CPSIT\T3importExport\InvalidConfigurationException
	 * @expectedExceptionCode 1454187910
	 */
	public function getThrowsExceptionIfClassDoesNotImplementFinisherInterface() {
		$configurationWithExistingClass = [
			'class' => DummyInvalidFinisher::class
		];
		$this->subject->get(
			$configurationWithExistingClass
		);
	}

	/**
	 * @test
	 */
	public function getReturnsFinisher() {
		$identifier = 'fooIdentifier';
		$validClass = DummyValidFinisher::class;
		$settings = [
			'class' => $validClass,
		];
		$mockObjectManager = $this->getMock(
			ObjectManager::class, ['get']
		);
		$this->subject->injectObjectManager($mockObjectManager);
		$mockFinisher = $this->getMock($validClass);
		$mockObjectManager->expects($this->once())
			->method('get')
			->with($validClass)
			->will($this->returnValue($mockFinisher));
		$this->assertEquals(
			$mockFinisher,
			$this->subject->get($settings, $identifier)
		);
	}
}
