<?php
namespace CPSIT\T3import\Tests\Unit\Component;

use CPSIT\T3import\Component\AbstractComponent;
use CPSIT\T3import\Service\InvalidConfigurationException;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2016 Dirk Wenzel <dirk.wenzel@cps-it.de>
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
class AbstractComponentTest extends UnitTestCase {

	/**
	 * @var \CPSIT\T3import\Component\AbstractComponent
	 */
	protected $subject;

	/**
	 * set up
	 */
	public function setUp() {
		$this->subject = $this->getAccessibleMockForAbstractClass(
			AbstractComponent::class
		);
	}

	/**
	 * @test
	 */
	public function getConfigurationInitiallyReturnsNull() {
		$this->assertNull(
			$this->subject->getConfiguration()
		);
	}

	/**
	 * @test
	 */
	public function setConfigurationSetsValidConfiguration() {
		$configuration = ['foo'];

		$this->subject->expects($this->once())
			->method('isConfigurationValid')
			->will($this->returnValue(true));
		$this->subject->setConfiguration($configuration);

		$this->assertSame(
			$configuration,
			$this->subject->getConfiguration()
		);
	}

	/**
	 * @test
	 * @expectedException \CPSIT\T3import\Service\InvalidConfigurationException
	 * @expectedExceptionCode 1451659793
	 */
	public function setConfigurationThrowsExceptionForInvalidConfiguration() {
		$configuration = ['foo'];

		$this->subject->expects($this->once())
			->method('isConfigurationValid')
			->will($this->returnValue(false));
		$this->subject->setConfiguration($configuration);
	}
}