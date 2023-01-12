<?php
namespace CPSIT\T3importExport\Configuration\Module;

use CPSIT\T3importExport\Controller\ExportController;
use DWenzel\T3extensionTools\Configuration\ModuleRegistrationInterface;
use DWenzel\T3extensionTools\Configuration\ModuleRegistrationTrait;
use CPSIT\T3importExport\Configuration\Extension;

class ExportModuleRegistration implements ModuleRegistrationInterface
{
    use ModuleRegistrationTrait;

    static protected string $subModuleName = 'Export';
    static protected string $mainModuleName = 'system';
    static protected string $vendorExtensionName = Extension::VENDOR_NAME . '.' . Extension::NAME;
    static protected array $controllerActions = [
        ExportController::class => 'index,exportTask,exportSet'
    ];

    static protected string $position = 'bottom';
    static protected array $moduleConfiguration = [
        'access' => 'user,group',
        'icon' => 'EXT:t3import_export/Resources/Public/Icons/module_export.svg',
        'labels' => 'LLL:EXT:t3import_export/Resources/Private/Language/locallang_export.xlf',
    ];
}
