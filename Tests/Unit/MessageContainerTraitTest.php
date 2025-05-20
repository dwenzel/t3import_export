<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit;

use CPSIT\T3importExport\Messaging\MessageContainer;
use CPSIT\T3importExport\Messaging\MessageContainerTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017 Dirk Wenzel <wenzel@cps-it.de>
 *  All rights reserved
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
 ***************************************************************/

/**
 * Class MessageContainerTraitTest
 */
class MessageContainerTraitTest extends TestCase
{
    /**
     * subject
     * @var MessageContainerTrait&MockObject
     */
    protected $subject;

    /**
     * @var MessageContainer&MockObject
     */
    protected $messageContainer;

    /**
     * set up subject
     */
    protected function setUp(): void
    {
        // Create message container mock directly
        $this->messageContainer = $this->createMock(MessageContainer::class);

        // In PHPUnit 12, getMockForTrait is removed - use anonymous class instead
        $this->subject = new class($this->messageContainer) {
            use MessageContainerTrait;

            protected MessageContainer $messageContainer;
        };
    }

    #[Test]
    public function testGetMessagesReturnsMessagesFromContainer(): void
    {
        $messages = ['foo'];
        $this->messageContainer->expects($this->once())
            ->method('getMessages')->willReturn($messages);
        $this->assertSame(
            $messages,
            $this->subject->getMessages()
        );
    }

    #[Test]
    public function testHasMessageWithIdReturnsResultFromMessageContainter(): void
    {
        $id = 123;
        $this->messageContainer->expects($this->once())
            ->method('hasMessageWithId')
            ->with($id)
            ->willReturn(true);

        $this->assertTrue(
            $this->subject->hasMessageWithId($id)
        );
    }
}
