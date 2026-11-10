<?php

declare(strict_types=1);

use CPSIT\T3importExport\Configuration\Extension;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    Extension::SVG_ICON_IDENTIFIER_JOBS => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:t3import_export/Resources/Public/Icons/tx_t3importexport_domain_model_job.svg',
    ],
    Extension::SVG_ICON_IDENTIFIER_MODULE_IMPORT => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:t3import_export/Resources/Public/Icons/module_import.svg',
    ],
    Extension::SVG_ICON_IDENTIFIER_MODULE_EXPORT => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:t3import_export/Resources/Public/Icons/module_export.svg',
    ],
];
