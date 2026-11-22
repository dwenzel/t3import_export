<?php

declare(strict_types=1);
/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2016 Dirk Wenzel <dirk.wenzel@cps-it.de>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

namespace CPSIT\T3importExport\Domain\Model;

use CPSIT\T3importExport\Utility\DeprecationUtility;
use Psr\Http\Message\StreamInterface;

/**
 * @deprecated since 4.0, will be removed in 5.0. Use
 * `CPSIT\ImportExportCore\Domain\Model\DataStream`.
 */
class DataStream extends \CPSIT\ImportExportCore\Domain\Model\DataStream implements DataStreamInterface
{
    public function __construct(?StreamInterface $stream = null)
    {
        DeprecationUtility::trigger(
            self::class . ' is deprecated since 4.0. Use ' . parent::class . '.',
            DeprecationUtility::CORE_ISSUE_URL
        );
        parent::__construct($stream);
    }
}
