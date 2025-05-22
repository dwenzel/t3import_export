<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Configuration;

use CPSIT\T3importExport\Command\ImportSetCommand;
use CPSIT\T3importExport\Command\ImportTaskCommand;
use CPSIT\T3importExport\Configuration\Module\ExportModuleRegistration;
use CPSIT\T3importExport\Configuration\Module\ImportModuleRegistration;
use DWenzel\T3extensionTools\Configuration\ExtensionConfiguration;
use DWenzel\T3extensionTools\Configuration\ModuleRegistrationInterface;
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
    final public const string KEY = 't3import_export';
    final public const string NAME = 'T3importExport';
    final public const string VENDOR_NAME = 'CPSIT';

    final public const array COMMANDS_TO_REGISTER = [
        ImportSetCommand::DEFAULT_NAME => [
            'class' => ImportSetCommand::class,
        ],
        ImportTaskCommand::DEFAULT_NAME => [
            'class' => ImportTaskCommand::class,
        ],
    ];

    final public const string SVG_ICON_IDENTIFIER_JOBS = 'jobs';
    final public const string SVG_ICON_IDENTIFIER_MODULE_IMPORT = 't3import_export-module-import';
    final public const string SVG_ICON_IDENTIFIER_MODULE_EXPORT = 't3import_export-module-export';
    /**
     * SVG icons to register
     */
    protected const SVG_ICONS_TO_REGISTER = [
        self::SVG_ICON_IDENTIFIER_JOBS => 'EXT:t3import_export/Resources/Public/Icons/tx_t3importexport_domain_model_job.svg',
        self::SVG_ICON_IDENTIFIER_MODULE_IMPORT => 'EXT:t3import_export/Resources/Public/Icons/module_import.svg',
        self::SVG_ICON_IDENTIFIER_MODULE_EXPORT => 'EXT:t3import_export/Resources/Public/Icons/module_export.svg',
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
}
