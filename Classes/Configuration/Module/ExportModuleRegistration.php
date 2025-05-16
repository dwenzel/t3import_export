<?php

declare(strict_types=1);
namespace CPSIT\T3importExport\Configuration\Module;

use CPSIT\T3importExport\Configuration\Extension;
use CPSIT\T3importExport\Controller\ExportController;

/**
 *@todo Module Registration has changed. This class is kept for reference only
 */
class ExportModuleRegistration
{
    protected static string $subModuleName = 'Export';
    protected static string $mainModuleName = 'system';
    protected static string $vendorExtensionName = Extension::VENDOR_NAME . '.' . Extension::NAME;
    protected static array $controllerActions = [
        ExportController::class => 'index,exportTask,exportSet',
    ];

    protected static string $position = 'bottom';
    protected static array $moduleConfiguration = [
        'access' => 'user,group',
        'icon' => 'EXT:t3import_export/Resources/Public/Icons/module_export.svg',
        'labels' => 'LLL:EXT:t3import_export/Resources/Private/Language/locallang_export.xlf',
    ];
}
