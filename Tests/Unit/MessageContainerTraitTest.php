<?php

namespace CPSIT\T3importExport\Tests\Unit;

use CPSIT\T3importExport\Messaging\MessageContainer;
use CPSIT\T3importExport\Messaging\MessageContainerTrait;
use CPSIT\T3importExport\Tests\Unit\Traits\MockMessageContainerTrait;
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
    use MockMessageContainerTrait;

    /**
     * subject
     * @var \CPSIT\T3importExport\Messaging\MessageContainerTrait|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $subject;

    /**
     * set up subject
     */
    public function setUp(): void
    {
        $this->mockMessageContainer();
        $this->subject = $this->getMockBuilder(MessageContainerTrait::class)
            ->setConstructorArgs([$this->messageContainer])
            ->getMockForTrait();
    }

    /**
     * @test
     */
    public function getMessagesReturnsMessagesFromContainer() {
        $messages = ['foo'];
        $this->messageContainer->expects($this->once())
            ->method('getMessages')->willReturn($messages);
        $this->assertSame(
            $messages,
            $this->subject->getMessages()
        );
    }

    /**
     * @test
     */
    public function hasMessageWithIdReturnsResultFromMessageContainter() {
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
