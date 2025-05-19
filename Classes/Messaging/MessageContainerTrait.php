<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Messaging;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Copyright notice
 * (c) 2017. Dirk Wenzel <wenzel@cps-it.de>
 * All rights reserved
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

/**
 * Trait ErrorReportingTrait
 * Gathers and returns error messages
 */
trait MessageContainerTrait
{
    protected MessageContainer $messageContainer;

    public function __construct(?MessageContainer $messageContainer = null)
    {
        $this->messageContainer = $messageContainer ?? GeneralUtility::makeInstance(MessageContainer::class);
    }

    /**
     * Returns all messages.
     * Messages are kept.
     * @return array<Message>
     */
    public function getMessages(): array
    {
        return $this->messageContainer->getMessages();
    }

    /**
     * Returns and purges all messages from the message container
     */
    public function getAndPurgeMessages(): array
    {
        $messages = $this->messageContainer->getMessages();
        $this->messageContainer->clear();

        return $messages;
    }

    /**
     * Tells by id if a container has a certain message
     * Note: not all messages must have an id!
     *
     * @return bool
     */
    public function hasMessageWithId($id)
    {
        return $this->messageContainer->hasMessageWithId($id);
    }
}
