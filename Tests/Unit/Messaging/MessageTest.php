<?php

declare(strict_types=1);
namespace CPSIT\T3importExport\Tests\Unit\Messaging;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017 Dirk Wenzel <wenzel@cps-it.de>
 *  All rights reserved
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * A copy is found in the text file GPL.txt, and important notices to the license
 * from the author are found in LICENSE.txt distributed with these scripts.
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use CPSIT\T3importExport\Messaging\Message;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;

/**
 * Class MessageTest
 */
class MessageTest extends TestCase
{
    /**
     * @var Message
     */
    protected Message $subject;


    #[Test]
    public function constructorSetsProperties(): void
    {
        $message = 'foo';
        $title = 'bar';
        $severity = ContextualFeedbackSeverity::ERROR;
        $id = 12;
        $additionalInformation = ['baz'];


        $this->subject = new Message($message, $title, $severity, $id, $additionalInformation);
        $this->assertSame(
            $message,
            $this->subject->getMessage()
        );

        $this->assertSame(
            $title,
            $this->subject->getTitle()
        );

        $this->assertSame(
            $severity,
            $this->subject->getSeverity()
        );

        $this->assertSame(
            $id,
            $this->subject->getId()
        );

        $this->assertSame(
            $additionalInformation,
            $this->subject->getAdditionalInformation()
        );
    }
    #[Test]
    public function constructorSetsDefaultValues(): void
    {
        $message = 'foo';
        $defaultTitle = '';
        $defaultSeverity = ContextualFeedbackSeverity::OK;
        $additionalInformation = [];

        $this->subject = new Message($message, $defaultTitle, $defaultSeverity, null, $additionalInformation);
        $this->assertSame(
            $defaultTitle,
            $this->subject->getTitle()
        );

        $this->assertSame(
            $defaultSeverity,
            $this->subject->getSeverity()
        );

        $this->assertSame(
            $additionalInformation,
            $this->subject->getAdditionalInformation()
        );
        $this->assertNull(
            $this->subject->getId()
        );
    }
}
