<?php
namespace CPSIT\T3importExport\Messaging;

use TYPO3\CMS\Core\Messaging\AbstractMessage;

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
 * Class Message
 */
class Message extends AbstractMessage
{
    /**
     * additional information
     *
     * @var array
     */
    protected $additionalInformation = [];

    /**
     * message id
     *
     * @var int
     */
    protected $id;

    /**
     * constructor
     * @param string $message The message
     * @param string $title Optional title
     * @param int $severity Optional severity, must be one of \TYPO3\CMS\Core\Messaging\FlashMessage constants
     * @param int|null $id
     * @param array|null $additionalInformation Additional information
     */
    public function __construct($message, $title = '', $severity = self::OK, $id = null, array $additionalInformation = null)
    {
        $this->setMessage($message);
        $this->setTitle($title);
        $this->setSeverity($severity);
        if (!is_null($additionalInformation)) {
            $this->setAdditionalInformation($additionalInformation);
        }
        if (is_int($id)) {
            $this->id = $id;
        }
    }

    /**
     * Get the ID
     * @return int|null
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Get the additional information
     * @return array
     */
    public function getAdditionalInformation() {
        return $this->additionalInformation;
    }

    /**
     * Set the additional information
     * @param array $additionalInformation
     */
    public function setAdditionalInformation(array $additionalInformation) {
        $this->additionalInformation = $additionalInformation;
    }
}
