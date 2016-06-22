<?php
namespace CPSIT\T3importExport\Tests\Unit\Domain\Model;

use CPSIT\T3importExport\Domain\Model\ExportTarget;
use CPSIT\T3importExport\Domain\Model\TaskResult;
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
class TaskResultTest extends UnitTestCase
{

	/**
	 * @test
	 */
	public function testAddAndRemoveObjectsToIterator()
	{
		/** @var TaskResult|\PHPUnit_Framework_MockObject_MockObject $list */
		$list = new TaskResult();

		$obj1 = new \stdClass();
		$obj2 = new \stdClass();
		$obj3 = new \stdClass();

		$list->add($obj1);
		$list->add($obj2);
		$list->add($obj3);

		$this->assertEquals(3, $list->count());
		$this->assertTrue($list->removeElement($obj1));
		$this->assertEquals(2, $list->count());
		$this->assertFalse(in_array($obj1,$list->toArray(), true));

		$this->assertTrue($list->removeIndex(0));
		$this->assertEquals(1, $list->count());
		$this->assertFalse(in_array($obj1,$list->toArray(), true));

		$list->setElements([$obj1, $obj2]);
		$this->assertTrue(in_array($obj1,$list->toArray(), true));
		$this->assertTrue(in_array($obj2,$list->toArray(), true));
		$this->assertEquals(2, $list->count());
	}
	/**
	 * @test
	 */
	public function testWhenMockThreeIterationWithNoKey()
	{
		/** @var TaskResult|\PHPUnit_Framework_MockObject_MockObject $list */
		$list = $this->getMock(TaskResult::class);

		$expectedValues = array(
			'This is the first item',
			'This is the second item',
			'And the final item'
		);

		$this->mockIterator($list, $expectedValues);

		$counter = 0;
		$values = array();
		foreach ($list as $value)
		{
			$values[] = $value;
			$counter++;
		}
		$this->assertEquals(3, $counter);

		$this->assertEquals($expectedValues, $values);

		$info = ['someThing'];

		$list->expects($this->once())
			->method('setInfo')
			->with($info);

		$list->expects($this->once())
			->method('getInfo')
			->will($this->returnValue($info));

		$list->setInfo($info);
		$this->assertEquals($info, $list->getInfo());
	}

	/**
	 * Mock iterator
	 *
	 * This attaches all the required expectations in the right order so that
	 * our iterator will act like an iterator
	 * @param \Iterator|\PHPUnit_Framework_MockObject_MockObject $iterator
	 * @param array $items
	 * @param $includeCallsToKey = false
	 */
	private function mockIterator(
		\Iterator $iterator,
		array $items,
		$includeCallsToKey = false
	)
	{
		$iterator->expects($this->at(0))
			->method('rewind');
		$counter = 1;
		foreach ($items as $k => $v)
		{
			$iterator->expects($this->at($counter++))
				->method('valid')
				->will($this->returnValue(true));
			$iterator->expects($this->at($counter++))
				->method('current')
				->will($this->returnValue($v));
			if ($includeCallsToKey)
			{
				$iterator->expects($this->at($counter++))
					->method('key')
					->will($this->returnValue($k));
			}
			$iterator->expects($this->at($counter++))
				->method('next');
		}
		$iterator->expects($this->at($counter))
			->method('valid')
			->will($this->returnValue(false));
	}
}
