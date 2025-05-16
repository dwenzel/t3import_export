<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Domain\Model;

use CPSIT\T3importExport\Domain\Model\TaskResult;
use CPSIT\T3importExport\Messaging\MessageContainer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;

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
class TaskResultTest extends TestCase
{
    protected TaskResult $subject;
    protected MessageContainer&MockObject $messageContainer;

    protected function setUp(): void
    {
        // Create message container mock directly
        $this->messageContainer = $this->createMock(MessageContainer::class);
        $this->subject = new TaskResult($this->messageContainer);
    }

    public function testAddAndRemoveObjectsToIterator(): void
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
        $this->assertFalse(in_array($obj1, $list->toArray(), true));

        $this->assertTrue($list->removeIndex(0));
        $this->assertEquals(1, $list->count());
        $this->assertFalse(in_array($obj1, $list->toArray(), true));

        $list->setElements([$obj1, $obj2]);
        $this->assertTrue(in_array($obj1, $list->toArray(), true));
        $this->assertTrue(in_array($obj2, $list->toArray(), true));
        $this->assertEquals(2, $list->count());
    }

    public function testWhenMockThreeIterationWithNoKey(): void
    {
        // fixme: This test is way to complicated and should be replaced
        $this->markTestSkipped('This test is overly complicated and should be replaced');
        /** @var TaskResult|\PHPUnit_Framework_MockObject_MockObject $list */
        $list = $this->getMockBuilder(TaskResult::class)->getMock();

        $expectedValues = ['This is the first item', 'This is the second item', 'And the final item'];

        $this->mockIterator($list, $expectedValues);

        $counter = 0;
        $values = [];
        foreach ($list as $value) {
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
     */
    private function mockIterator(
        \Iterator $iterator,
        array $items
    ) {
        $iterator->expects($this->at(0))
            ->method('rewind');
        $counter = 1;
        foreach ($items as $k => $v) {
            $iterator->expects($this->at($counter++))
                ->method('valid')
                ->will($this->returnValue(true));
            $iterator->expects($this->at($counter++))
                ->method('current')
                ->will($this->returnValue($v));
            $iterator->expects($this->at($counter++))
                ->method('next');
        }
        $iterator->expects($this->at($counter))
            ->method('valid')
            ->will($this->returnValue(false));
    }

    public function testRemoveElementsReturnsFalseForNonExistingElement(): void
    {
        $nonExistingElement = 'foo';
        $this->assertFalse(
            $this->subject->removeElement($nonExistingElement)
        );
    }

    public function testKeyInitiallyReturnsZero(): void
    {
        $this->assertSame(
            0,
            $this->subject->key()
        );
    }

    public function testKeyReturnsPosition(): void
    {
        $element = new \stdClass();
        $this->subject->add($element);
        $this->subject->next();
        $this->assertSame(
            1,
            $this->subject->key()
        );
    }

    public function testCountInitiallyReturnsZero(): void
    {
        $this->assertSame(
            0,
            $this->subject->count()
        );
    }

    public function testCountReturnsSize(): void
    {
        $elements = [
            'foo',
            'bar',
        ];
        $size = count($elements);

        $this->subject->setElements($elements);
        $this->assertSame(
            $size,
            $this->subject->count()
        );
    }

    public function testGetMessagesReturnsMessagesFromContainer(): void
    {
        $messages = ['foo'];
        $this->messageContainer->expects($this->once())
            ->method('getMessages')
            ->willReturn($messages);

        $this->assertSame(
            $messages,
            $this->subject->getMessages()
        );
    }
}
