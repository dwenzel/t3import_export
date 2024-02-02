<?php
namespace CPSIT\T3importExport\Configuration\Module;

use DWenzel\T3extensionTools\Configuration\ModuleRegistrationInterface;
use DWenzel\T3extensionTools\Configuration\ModuleRegistrationTrait;
use CPSIT\T3importExport\Configuration\Extension;

class ImportModuleRegistration implements ModuleRegistrationInterface
{
    use ModuleRegistrationTrait;

    final public const ROUTE = 'site_ApiToken';

    static protected string $subModuleName = 'Import';
    static protected string $mainModuleName = 'system';
    static protected string $vendorExtensionName = Extension::VENDOR_NAME . '.' . Extension::NAME;
    static protected array $controllerActions = [
        'Import' => 'index,importTask,importSet'
    ];

    static protected string $position = 'bottom';
    static protected array $moduleConfiguration = [
        'access' => 'user,group',
        'icon' => 'EXT:t3import_export/Resources/Public/Icons/module_import.svg',
        'labels' => 'LLL:EXT:t3import_export/Resources/Private/Language/locallang_import.xlf',
    ];
}
