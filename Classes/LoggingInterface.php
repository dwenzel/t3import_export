<?php

namespace CPSIT\T3importExport;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

interface LoggingInterface
{
    public const DEFAULT_MESSAGE_TITLE = 'Message';
    public const DEFAULT_UNKNOWN_MESSAGE = 'Message with unknown ID';
    public const ERROR_UNKNOWN_MESSAGE = 'An unknown error occurred';
    public const ERROR_UNKNOWN_TITLE = 'Unknown error';
    public const NOTICE_UNKNOWN_MESSAGE = 'Notice with unknown ID';
    public const NOTICE_UNKNOWN_TITLE = 'Notice';

    /**
     * Gets all messages
     * @return array
     */
    public function getMessages();

    /**
     * Returns and purges all messages from the message container
     * @return array
     */
    public function getAndPurgeMessages(): array;
}
