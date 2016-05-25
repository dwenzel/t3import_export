<?php
namespace CPSIT\T3importExport\Tests;

use CPSIT\T3importExport\ConfigurableTrait;
use TYPO3\CMS\Core\Tests\UnitTestCase;

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
class ConfigurableTraitTest extends UnitTestCase {

	/**
	 * @var ConfigurableTrait
	 */
	protected $subject;

	public function setUp() {
		$this->subject = $this->getMockForTrait(
			ConfigurableTrait::class
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
	 * @expectedException \CPSIT\T3importExport\InvalidConfigurationException
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
