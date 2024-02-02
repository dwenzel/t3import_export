<?php

namespace CPSIT\T3importExport\Domain\Model;

use DateTime;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2022 Dirk Wenzel <wenzel@cps-it.de>
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
class QueueItem extends AbstractEntity
{
    final public const TABLE = 'tx_t3importexport_domain_model_queueitem';
    final public const STATUS_NEW = 0;
    final public const STATUS_PROCESSING = 1;
    final public const STATUS_FINISHED = 2;
    final public const STATUS_FAILED = 3;

    final public const FIELD_IDENTIFIER = 'identifier';
    final public const FIELD_CREATED = 'created_date';
    final public const FIELD_STARTED = 'started_date';
    final public const FIELD_FINISHED = 'finished_date';
    final public const FIELD_CHECKSUM = 'checksum';
    final public const FIELD_DATA = 'data';
    final public const FIELD_STATUS = 'status';
    final public const FIELD_UID = 'uid';


    /**
     * allowed status values
     */
    final public const STATUS = [
        self::STATUS_NEW,
        self::STATUS_PROCESSING,
        self::STATUS_FINISHED,
        self::STATUS_FAILED
    ];

    protected DateTime $createdDate;
    protected DateTime $startedDate;
    protected DateTime $finishedDate;
    protected string $checksum;
    protected string $data;
    protected int $status;
}
