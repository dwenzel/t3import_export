<?php
namespace CPSIT\T3importExport\Tests\Unit\Persistence\Factory;

use CPSIT\T3importExport\Persistence\DataTargetInterface;
use CPSIT\T3importExport\Persistence\Factory\DataTargetFactory;
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
 * Class DummyMissingTargetInterfaceClass
 *
 * @package CPSIT\T3importExport\Tests\Unit\Persistence\Factory
 */
class DummyMissingTargetInterfaceClass {}

/**
 * Class DummyTargetObjectClass
 *
 * @package CPSIT\T3importExport\Tests\Unit\Persistence\Factory
 */
class DummyTargetObjectClass {}

/**
 * Class DataTargetFactoryTest
 *
 * @package CPSIT\T3importExport\Tests\Unit\Persistence\Factory
 * @coversDefaultClass \CPSIT\T3importExport\Persistence\Factory\DataTargetFactory
 */
class DataTargetFactoryTest extends UnitTestCase {

	/**
	 * @var DataTargetFactory
	 */
	protected $subject;

	/**
	 * set up
	 */
	public function setUp() {
		$this->subject = $this->getMock(
			DataTargetFactory::class, ['dummy']
		);
	}

	/**
	 * @test
	 * @expectedException \CPSIT\T3importExport\MissingClassException
	 * @expectedExceptionCode 1451043513
	 */
	public function getThrowsExceptionForMissingTargetClass() {
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
	public function getThrowsExceptionForMissingInterface() {
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
	public function getThrowsExceptionForMissingObjectClass() {
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
	public function getReturnsDefaultDataTarget() {
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
}
