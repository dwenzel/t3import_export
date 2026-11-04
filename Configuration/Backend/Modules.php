<?php

declare(strict_types=1);
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2024 Dirk Wenzel <wenzel@cps-it.de>
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

use CPSIT\T3importExport\Configuration\Extension;
use CPSIT\T3importExport\Controller\ExportController;
use CPSIT\T3importExport\Controller\ImportController;

return [
    'import' => [
        'parent' => 'system',
        'position' => ['bottom'],
        'access' => 'user,group',
        //'path' => '/module/path/...'
        'labels' => 'LLL:EXT:t3import_export/Resources/Private/Language/locallang_import.xlf',
        'iconIdentifier' => Extension::SVG_ICON_IDENTIFIER_MODULE_IMPORT,
        'extensionName' => Extension::NAME,
        'controllerActions' => [
            ImportController::class => 'index,importTask,importSet',
        ]
    ],
    'export' => [
        'parent' => 'system',
        'position' => ['bottom'],
        'access' => 'user,group',
        //'path' => '/module/path/...'
        'labels' => 'LLL:EXT:t3import_export/Resources/Private/Language/locallang_export.xlf',
        'iconIdentifier' => Extension::SVG_ICON_IDENTIFIER_MODULE_EXPORT,
        'extensionName' => Extension::NAME,
        'controllerActions' => [
            ExportController::class => 'index,exportTask,exportSet',
        ]
    ]
];
