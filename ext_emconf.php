<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "t3import_export".
 *
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = [
    'title' => 'Import & Export',
    'description' => 'General import and export tool for the TYPO3 CMS',
    'category' => 'module',
    'author' => 'Dirk Wenzel',
    'author_email' => 'dirk.wenzel@cps-it.de',
    'author_company' => '',
    'state' => 'stable',
    'version' => '3.0.1',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-13.4.99',
            'php' => '8.3.0-8.4.99',
            't3extension_tools' => '4.0.0-5.99.99',
        ],
        'conflicts' => [],
        'suggests' => []
    ]
];
