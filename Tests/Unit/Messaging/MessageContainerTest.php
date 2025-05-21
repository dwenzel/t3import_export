<?php

declare(strict_types=1);
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
use CPSIT\ImportExportCore\Messaging\Message;
use CPSIT\ImportExportCore\Messaging\MessageContainer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Class MessageReportingTraitTest
 */
class MessageContainerTest extends TestCase
{
    /**
     * @var MessageContainer
     */
    protected $subject;

    /**
     * set up subject
     */
    protected function setUp(): void
    {
        // No need to mock methods, use actual implementation
        $this->subject = new MessageContainer();
    }

    #[Test]
    public function getMessagesInitiallyReturnsEmptyArray(): void
    {
        $expected = [];
        $this->assertSame(
            $expected,
            $this->subject->getMessages()
        );
    }

    #[Test]
    public function singleMessageCanBeAdded(): void
    {
        /** @var Message $message */
        $message = $this->createMock(Message::class);
        $this->subject->addMessage($message);

        $expected = [$message];

        $this->assertSame(
            $expected,
            $this->subject->getMessages()
        );
    }

    #[Test]
    public function multipleMessagesCanBeAdded(): void
    {
        /** @var Message $message */
        $message = $this->createMock(Message::class);

        $messages = [$message];
        $this->subject->addMessages($messages);
        $this->assertSame(
            $messages,
            $this->subject->getMessages()
        );
    }

    #[Test]
    public function messagesCanBeCleared(): void
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

    #[Test]
    public function hasMessageInitiallyReturnsFalse(): void
    {
        $nonExistingId = 4447;
        $this->subject->clear();
        $this->assertFalse(
            $this->subject->hasMessageWithId($nonExistingId)
        );
    }

    #[Test]
    public function hasMessageReturnsTrueForMessageInContainer(): void
    {
        $id = 7;
        $mockMessage = $this->createMock(Message::class);
        $mockMessage->method('getId')
            ->willReturn($id);
        $this->subject->addMessage($mockMessage);
        $this->assertTrue(
            $this->subject->hasMessageWithId($id)
        );
    }
}
