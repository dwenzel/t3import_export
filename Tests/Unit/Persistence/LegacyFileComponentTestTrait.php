<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Persistence;

use CPSIT\ImportExportCore\Resource\FileResourceLoader;
use CPSIT\ImportExportCore\Resource\PathResolver;
use CPSIT\T3importExport\Utility\DeprecationUtility;

/**
 * Temporary directory and collected deprecation messages for the tests of
 * the deprecated file components
 */
trait LegacyFileComponentTestTrait
{
    protected string $temporaryDirectory = '';

    /**
     * @var string[]
     */
    protected array $deprecations = [];

    protected function setUpFilesAndDeprecations(): void
    {
        $this->temporaryDirectory = sys_get_temp_dir() . '/t3import_export_' . bin2hex(random_bytes(6));
        mkdir($this->temporaryDirectory);
        DeprecationUtility::reset();
        $this->deprecations = [];
        set_error_handler(
            function (int $level, string $message): bool {
                $this->deprecations[] = $message;
                return true;
            },
            E_USER_DEPRECATED
        );
    }

    protected function tearDownFilesAndDeprecations(): void
    {
        restore_error_handler();
        DeprecationUtility::reset();
        foreach (glob($this->temporaryDirectory . '/*') ?: [] as $file) {
            unlink($file);
        }
        if (is_dir($this->temporaryDirectory)) {
            rmdir($this->temporaryDirectory);
        }
    }

    protected function writeFile(string $name, string $content): void
    {
        file_put_contents($this->temporaryDirectory . '/' . $name, $content);
    }

    protected function pathResolver(): PathResolver
    {
        return new PathResolver($this->temporaryDirectory);
    }

    protected function resourceLoader(): FileResourceLoader
    {
        return new FileResourceLoader($this->pathResolver());
    }

    protected static function assertDeprecationOf(string $class, array $deprecations): void
    {
        self::assertCount(1, $deprecations);
        self::assertStringContainsString($class . ' is deprecated since 4.0.', $deprecations[0]);
        self::assertStringContainsString(DeprecationUtility::FILE_COMPONENTS_ISSUE_URL, $deprecations[0]);
    }
}
