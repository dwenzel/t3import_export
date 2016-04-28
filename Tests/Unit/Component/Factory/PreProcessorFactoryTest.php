<?php
namespace CPSIT\T3importExport\Tests\Unit\Component\Factory;

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
 * Class DummyInvalidPreProcessor
 * Does not implement PreProcessorInterface
 *
 * @package CPSIT\T3importExport\Tests\Component\Factory
 */
class DummyInvalidPreProcessor {
}

/**
 * Class DummyValidPreProcessor
 *
 * @package CPSIT\T3importExport\Tests\Unit\Component\Factory
 */
class DummyValidPreProcessor
	extends AbstractPreProcessor
	implements PreProcessorInterface {
	/**
	 * @param array $configuration
	 * @param array $record
	 * @return bool
	 */
	function process($configuration, &$record) {
		return true;
	}
}

/**
 * Class PreProcessorFactoryTest
 *
 * @package CPSIT\T3importExport\Tests\Unit\Component\Factory
 */
class PreProcessorFactoryTest extends UnitTestCase {

	/**
	 * @var \CPSIT\T3importExport\Component\Factory\PreProcessorFactory
	 */
	protected $subject;

	/**
	 *
	 */
	public function setUp() {
		$this->subject = $this->getAccessibleMock(
			PreProcessorFactory::class,
			['dummy']
		);
	}

	/**
	 * @test
	 * @expectedException \CPSIT\T3importExport\InvalidConfigurationException
	 * @expectedExceptionCode 1447427020
	 */
	public function getThrowsInvalidConfigurationExceptionIfClassIsNotSet() {
		$configurationWithoutClassName = ['bar'];

		$this->subject->get($configurationWithoutClassName, 'fooIdentifier');
	}

	/**
	 * @test
	 * @expectedException \CPSIT\T3importExport\InvalidConfigurationException
	 * @expectedExceptionCode 1447427184
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
	 * @expectedExceptionCode 1447428235
	 */
	public function getThrowsExceptionIfClassDoesNotImplementPreProcessorInterface() {
		$configurationWithExistingClass = [
			'class' => DummyInvalidPreProcessor::class
		];
		$this->subject->get(
			$configurationWithExistingClass
		);
	}

	/**
	 * @test
	 */
	public function getReturnsPreProcessor() {
		$identifier = 'fooIdentifier';
		$validClass = DummyValidPreProcessor::class;
		$validSingleConfiguration = ['foo' => 'bar'];
		$settings = [
			'class' => $validClass,
		];
		$mockObjectManager = $this->getMock(
			ObjectManager::class, ['get']
		);
		$this->subject->injectObjectManager($mockObjectManager);
		$mockPreProcessor = $this->getMock($validClass);
		$mockObjectManager->expects($this->once())
			->method('get')
			->with($validClass)
			->will($this->returnValue($mockPreProcessor));
		$this->assertEquals(
			$mockPreProcessor,
			$this->subject->get($settings, $identifier)
		);
	}
}
