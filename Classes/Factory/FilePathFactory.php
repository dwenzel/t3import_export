<?php

namespace CPSIT\T3importExport\Factory;

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

use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Class FilePathFactory
 */
class FilePathFactory
{
    /**
     * Converts an array of path parts into a path string.
     * Parts are sanitized and separated by the DIRECTORY_SEPARATOR
     * @param array $parts Flat array of path parts
     * @return string path
     */
    public function createFromParts(array $parts)
    {
        array_walk($parts, function (&$item) {
            $item = PathUtility::sanitizeTrailingSeparator($item, DIRECTORY_SEPARATOR);
        });

        return implode('', $parts);
    }
}