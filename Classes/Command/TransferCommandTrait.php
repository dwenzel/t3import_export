<?php
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

use CPSIT\T3importExport\Service\DataTransferProcessor;
use DWenzel\T3extensionTools\Configuration\ConfigurationManagerTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

/**
 * Class TransferCommandController
 */
trait TransferCommandTrait
{
    use ConfigurationManagerTrait;

    /**
     * @var array
     */
    protected array $settings;

    protected DataTransferProcessor $dataTransferProcessor;


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
        $extbaseFrameworkConfiguration = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK
        );

        if (isset($extbaseFrameworkConfiguration['settings'][static::SETTINGS_KEY])) {
            $this->settings = $extbaseFrameworkConfiguration['settings'][static::SETTINGS_KEY];
        }
    }
}
