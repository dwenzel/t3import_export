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
    const DEFAULT_MESSAGE_TITLE = 'Message';
    const DEFAULT_UNKNOWN_MESSAGE = 'Message with unknown ID';
    const ERROR_UNKNOWN_MESSAGE = 'An unknown error occurred';
    const ERROR_UNKNOWN_TITLE = 'Unknown error';
    const NOTICE_UNKNOWN_MESSAGE = 'Notice with unknown ID';
    const NOTICE_UNKNOWN_TITLE = 'Notice';
}
