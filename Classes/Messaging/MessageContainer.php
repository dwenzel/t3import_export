<?php

namespace CPSIT\T3importExport\Messaging;

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
 * Class MessageContainer
 * Gathers messages
 */
class MessageContainer
{
    /**
     * @var array
     */
    protected $messages = [];

    /**
     * Add message to list of messages
     *
     * @param Message $message
     */
    public function addMessage($message)
    {
        $this->messages[] = $message;
    }

    /**
     * Add messages to list of messages
     *
     * @param array $messages
     */
    public function addMessages($messages)
    {
        foreach ($messages as $message) {
            $this->addMessage($message);
        }
    }

    /**
     * Get list of messages
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * Reset messages
     */
    public function clear()
    {
        $this->messages = [];
    }
}
