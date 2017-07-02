<?php

namespace CPSIT\T3importExport;
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

use CPSIT\T3importExport\Messaging\Message;

/**
 * Trait LoggingTrait
 * Provides logging capabilities
 */
trait LoggingTrait
{
    use MessageContainerTrait, ObjectManagerTrait;

    /**
     * Returns error codes for current component.
     * Must be an array in the form
     * [
     *  <id> => ['errorTitle', 'errorDescription']
     * ]
     * 'errorDescription' may contain placeholder (%s) for arguments.
     * @return array
     */
    abstract public function getErrorCodes();

    /**
     * Creates an error message and adds it to the message container
     *
     * @param int $id Error id
     * @param array $arguments Optional arguments. Will be used as arguments for formatted message.
     */
    public function logError($id, $arguments = null)
    {
        $title = LoggingInterface::ERROR_UNKNOWN_TITLE;
        $description = LoggingInterface::ERROR_UNKNOWN_MESSAGE;

        $errorCodes = $this->getErrorCodes();

        if (isset($errorCodes[$id])) {
            $title = $errorCodes[$id][0];
            $description = $errorCodes[$id][1];
            if (null !== $arguments) {
                array_unshift($arguments, $description);
                $description = call_user_func_array('sprintf', $arguments);
            }
        }
        $description .= PHP_EOL . 'Error ID ' . $id . ' in component ' . get_class($this);

        /** @var Message $message */
        $message = $this->objectManager->get(
            Message::class,
            $description,
            $title,
            Message::ERROR
        );
        $this->messageContainer->addMessage($message);
    }

}