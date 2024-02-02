<?php

namespace CPSIT\T3importExport\Configuration;

use CPSIT\T3importExport\Command\ImportCommandController;
use CPSIT\T3importExport\Command\ImportSetCommand;
use CPSIT\T3importExport\Command\ImportTaskCommand;
use CPSIT\T3importExport\Configuration\Module\ExportModuleRegistration;
use CPSIT\T3importExport\Configuration\Module\ImportModuleRegistration;
use DWenzel\T3extensionTools\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
class Extension extends ExtensionConfiguration
{
    final public const KEY = 't3import_export';
    final public const NAME = 'T3importExport';
    final public const VENDOR_NAME = 'CPSIT';

    final public const MODULES_TO_REGISTER = [
        ImportModuleRegistration::class,
        ExportModuleRegistration::class
    ];

    final public const COMMANDS_TO_REGISTER = [
        ImportSetCommand::DEFAULT_NAME => [
            'class' => ImportSetCommand::class,
        ],
        ImportTaskCommand::DEFAULT_NAME => [
            'class' => ImportTaskCommand::class,
        ],
    ];

    final public const SVG_ICON_IDENTIFIER_JOBS = 'jobs';
    /**
     * SVG icons to register
     */
    protected const SVG_ICONS_TO_REGISTER = [
        self::SVG_ICON_IDENTIFIER_JOBS => 'EXT:t3import_export/Resources/Public/Icons/tx_t3importexport_domain_model_job.svg',
    ];

    public function getCommandsToRegister(): array
    {
        /** @var Environment $environment */
        $environment = GeneralUtility::makeInstance(Environment::class);
        $version = GeneralUtility::makeInstance(Typo3Version::class);

        // Note: we disable console commands in cli context for TYPO3 version before 10.x
        // due to missing TypoScript configuration
        if ($version->getMajorVersion() < 10 && $environment::isCli()) {
            return [];
        }

        // enable console commands for backend modules since there is TypoScript available
        if ($version->getMajorVersion() >= 9) {
            return self::COMMANDS_TO_REGISTER;
        }

        return [];
    }

    /**
     * Register legacy command controllers for cli environment in TYPO3 9
     */
    public static function registerLegacyCommands(): void
    {
        $version = GeneralUtility::makeInstance(Typo3Version::class);
        /** @var Environment $environment */
        $environment = GeneralUtility::makeInstance(Environment::class);

        if ($version->getMajorVersion() === 9) {
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers']['t3importExportImport'] = \CPSIT\T3importExport\Legacy\Command\ImportCommandController::class;
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers']['t3importExportExport'] = \CPSIT\T3importExport\Legacy\Command\ExportCommandController::class;
        }

    }
}
