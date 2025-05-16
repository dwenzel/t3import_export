<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Core\ValueObject\PhpVersion;
use Rector\PostRector\Rector\NameImportingPostRector;
use Ssch\TYPO3Rector\Configuration\Typo3Option;
use Ssch\TYPO3Rector\Set\Typo3LevelSetList;

return static function (RectorConfig $rectorConfig): void {
    // If you want to override the number of spaces for your typoscript files you can define it here, the default value is 4
    $rectorConfig->ruleWithConfiguration(\Ssch\TYPO3Rector\Rector\v11\v4\MigrateFileFolderConfigurationMainFunctionRector::class, [
        \Ssch\TYPO3Rector\Rector\v11\v4\MigrateFileFolderConfigurationMainFunctionRector::TYPOSCRIPT_INDENTATION => 2,
    ]);

    $rectorConfig->sets([
        Typo3LevelSetList::UP_TO_TYPO3_13,
    ]);

    // Define your paths here
    $rectorConfig->paths([
        __DIR__ . '/Classes',
    ]);

    // Define yourself if needed
    $rectorConfig->phpVersion(PhpVersion::PHP_82);

    // If you use importNames(), you should set this option - otherwise, you may have unwanted behavior like class names replaced with FQN.
    $rectorConfig->importNames();
    $rectorConfig->importShortClasses();

    // Skip post rector to prevent too many changes at once
    $rectorConfig->removeService(NameImportingPostRector::class);

    $rectorConfig->skip([
        __DIR__ . '/.Build/',
        __DIR__ . '/vendor/',
        __DIR__ . '/Tests/',
    ]);

    // Useful rules
    $rectorConfig->rule(\Rector\TypeDeclaration\Rector\ClassMethod\AddReturnTypeDeclarationRector::class);
    $rectorConfig->rule(\Rector\TypeDeclaration\Rector\ClassMethod\AddParamTypeDeclarationRector::class);
};