<?php
namespace CPSIT\T3importExport\Tests\Unit\Messaging;

/**
 * Copyright notice
 * (c) 2017. Dirk Wenzel <wenzel@cps-it.de>
 * All rights reserved
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * A copy is found in the text file GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * This copyright notice MUST APPEAR in all copies of the script!
 */
use CPSIT\T3importExport\Messaging\Message;
use CPSIT\T3importExport\Messaging\MessageContainer;
use PHPUnit\Framework\TestCase;

/**
 * Class MessageReportingTraitTest
 */
class MessageContainerTest extends TestCase
{
    /**
     * @var MessageContainer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $subject;

    /**
     * set up subject
     */
    protected function setUp(): void
    {
        $this->subject = $this->getMockBuilder(MessageContainer::class)
            ->setMethods(['dummy'])->getMock();
    }

    /**
     * @test
     */
    public function getMessagesInitiallyReturnsEmptyArray()
    {
        $expected = [];
        $this->assertSame(
            $expected,
            $this->subject->getMessages()
        );
    }

    /**
     * @test
     */
    public function singleMessageCanBeAdded()
    {
        /** @var Message $message */
        $message = $this->getMockBuilder(Message::class)
            ->disableOriginalConstructor()->getMock();
        $this->subject->addMessage($message);

        $expected = [$message];

        $this->assertSame(
            $expected,
            $this->subject->getMessages()
        );
    }

    /**
     * @test
     */
    public function multipleMessagesCanBeAdded()
    {
        /** @var Message $message */
        $message = $this->getMockBuilder(Message::class)
            ->disableOriginalConstructor()->getMock();

        $messages = [$message];
        $this->subject->addMessages($messages);
        $this->assertSame(
            $messages,
            $this->subject->getMessages()
        );
    }

    /**
     * @test
     */
    public function messagesCanBeCleared()
    {
        $messages = ['foo'];
        $expected = [];
        $this->subject->addMessages($messages);
        $this->subject->clear();

        $this->assertSame(
            $expected,
            $this->subject->getMessages()
        );
    }

    /**
     * @test
     */
    public function hasMessageInitiallyReturnsFalse() {
        $nonExistingId = 4447;
        $this->subject->clear();
        $this->assertFalse(
            $this->subject->hasMessageWithId($nonExistingId)
        );
    }

    /**
     * @test
     */
    public function hasMessageReturnsTrueForMessageInContainer() {
        $id = 7;
        $mockMessage = $this->getMockBuilder(Message::class)->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();
        $mockMessage->expects($this->once())->method('getId')
            ->willReturn($id);
        $this->subject->addMessage($mockMessage);
        $this->assertTrue(
            $this->subject->hasMessageWithId($id)
        );
    }
}
