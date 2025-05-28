<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Command;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2018 Dirk Wenzel <wenzel@cps-it.de>
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
 * Class TransferCommandController
 */
trait TransferCommandTrait
{
    protected array $settings;

    public function withSettings(array $settings): self
    {
        $this->settings = $settings;
        return $this;
    }

    /**
     * initialize object
     */
    public function initializeObject(): void
    {
        $fullConfiguration = $this->configurationManager->getFullConfiguration(
        );

        //@todo: Check if this is the right path
        if (isset($fullConfiguration['settings'][static::SETTINGS_KEY])) {
            $this->settings = $fullConfiguration['settings'][static::SETTINGS_KEY];
        }

    }
}
