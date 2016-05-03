<?php
namespace CPSIT\T3importExport\Tests\Unit\Domain\Model;

use CPSIT\T3importExport\Domain\Model\ExportTarget;
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
class ExportTargetTest extends UnitTestCase {

	/**
	 * @var ExportTarget
	 */
	protected $subject;

	/**
	 * set up
	 */
	public function setUp() {
		$this->subject= $this->getAccessibleMock(
			ExportTarget::class, ['dummy']
		);
	}

	/**
	 * @test
	 */
	public function getTitleForStringInitiallyReturnsNull() {
		$this->assertNull(
			$this->subject->getTitle()
		);
	}


	/**
	 * @test
	 */
	public function getDescriptionForStringInitiallyReturnsNull() {
		$this->assertNull(
			$this->subject->getDescription()
		);
	}

	/**
	 * @test
	 */
	public function titleCanBeSet() {
		$this->subject->setTitle('foo');
		$this->assertSame(
			'foo',
			$this->subject->getTitle()
		);
	}

	/**
	 * @test
	 */
	public function descriptionCanBeSet() {
		$this->subject->setDescription('foo');
		$this->assertSame(
			'foo',
			$this->subject->getDescription()
		);
	}
}
