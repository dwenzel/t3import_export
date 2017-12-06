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
use CPSIT\T3importExport\Messaging\MessageContainerTrait;

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
     * Returns notice codes for current component.
     * Override this method in instances with actual codes.
     * Must be an array in the form
     * [
     *  <id> => ['title', 'description']
     * ]
     * 'description' may contain placeholder (%s) for arguments.
     * @return array
     */
    public function getNoticeCodes()
    {
        return [];
    }

    /**
     * Creates an error message and adds it to the message container
     *
     * @param int $id Error id
     * @param array $arguments Optional arguments. Will be used as arguments for formatted message.
     * @param array|null $additionalInformation Optional array with additional information
     */
    public function logError($id, $arguments = null, array $additionalInformation = null)
    {
        $codes = $this->getErrorCodes();
        $description = $this->renderDescription($id, $codes, $arguments, LoggingInterface::ERROR_UNKNOWN_MESSAGE);
        $title = $this->renderTitle($id, $codes, LoggingInterface::ERROR_UNKNOWN_TITLE);

        $this->logMessage($title, $description, Message::ERROR, $id, $additionalInformation);
    }

    /**
     * Creates a notice and adds it to the message container
     *
     * @param int $id Error id
     * @param array $arguments Optional arguments. Will be used as arguments for formatted message.
     * @param array|null $additionalInformation Optional array with additional information
     */
    public function logNotice($id, $arguments = null, array $additionalInformation = null)
    {
        $codes = $this->getNoticeCodes();
        $title = $this->renderTitle($id, $codes, LoggingInterface::NOTICE_UNKNOWN_TITLE);
        $description = $this->renderDescription($id, $codes, $arguments, LoggingInterface::NOTICE_UNKNOWN_MESSAGE);

        $this->logMessage($title, $description, Message::NOTICE, $id, $additionalInformation);;
    }

    /**
     * Logs a message
     *
     * @param $title
     * @param $description
     * @param int $severity
     * @param null int $id
     * @param array|null $additionalInformation
     */
    public function logMessage($title, $description, $severity = Message::OK, $id = null, array $additionalInformation = null)
    {
        /** @var Message $message */
        $message = $this->objectManager->get(
            Message::class,
            $description,
            $title,
            $severity,
            $id,
            $additionalInformation
        );
        $this->messageContainer->addMessage($message);
    }

    /**
     * Renders a description
     *
     * @param int $id
     * @param array $codes An array of codes.
     * @param array|null $arguments Optional arguments
     * @param string $default Default description
     * @return string
     */
    protected function renderDescription($id, $codes, $arguments, $default = LoggingInterface::DEFAULT_UNKNOWN_MESSAGE)
    {
        $description = $default;
        if (isset($codes[$id])) {
            $description = $codes[$id][1];
            if (null !== $arguments) {
                array_unshift($arguments, $description);
                $description = call_user_func_array('sprintf', $arguments);

            }
        }

        $description .= PHP_EOL . 'Message ID ' . $id . ' in component ' . get_class($this);

        return $description;
    }

    /**
     * Renders a title
     *
     * @param int $id Message ID
     * @param array $codes An array of codes.
     * @param string $default Default title
     * @return string
     */
    protected function renderTitle($id, array $codes, $default = LoggingInterface::DEFAULT_MESSAGE_TITLE)
    {
        if (isset($codes[$id])) {
            $title = $codes[$id][0];
            return $title;
        }

        return $default;
    }

}
