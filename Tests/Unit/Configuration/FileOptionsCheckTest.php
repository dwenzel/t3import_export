<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Configuration;

use CPSIT\T3importExport\Configuration\FileOptionsCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(FileOptionsCheck::class)]
class FileOptionsCheckTest extends TestCase
{
    /**
     * @var array<string, mixed>
     */
    private array $configuration = [];

    protected function setUp(): void
    {
        $this->configuration = $GLOBALS['TYPO3_CONF_VARS'] ?? [];
        unset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']);
    }

    protected function tearDown(): void
    {
        $GLOBALS['TYPO3_CONF_VARS'] = $this->configuration;
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function optionsOfVersion3DataProvider(): iterable
    {
        yield 'fileSourceDirectories' => ['fileSourceDirectories', ['fileadmin/import']];
        yield 'fileTargetDirectories' => ['fileTargetDirectories', ['fileadmin/export']];
        yield 'fileTargetExtensions' => ['fileTargetExtensions', 'csv,json'];
        yield 'empty list' => ['fileSourceDirectories', []];
    }

    #[Test]
    #[DataProvider('optionsOfVersion3DataProvider')]
    public function reportsOptionsOfVersion3(string $option, mixed $value): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export'][$option] = $value;

        $messages = FileOptionsCheck::messages();

        self::assertCount(1, $messages);
        self::assertStringContainsString("['" . $option . "']", $messages[0]);
    }

    #[Test]
    #[DataProvider('directoryOptionsOfVersion3DataProvider')]
    public function recommendsFileDirectoriesForDirectoryOptions(string $option): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export'][$option] = ['fileadmin/import'];

        self::assertStringContainsString("Use ['fileDirectories']", FileOptionsCheck::messages()[0]);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function directoryOptionsOfVersion3DataProvider(): iterable
    {
        yield 'fileSourceDirectories' => ['fileSourceDirectories'];
        yield 'fileTargetDirectories' => ['fileTargetDirectories'];
    }

    #[Test]
    public function tellsThatFileTargetExtensionsHasNoEquivalent(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['fileTargetExtensions'] = ['csv', 'json'];

        $message = FileOptionsCheck::messages()[0];

        self::assertStringNotContainsString('fileDirectories', $message);
        self::assertStringContainsString('no equivalent', $message);
        self::assertStringContainsString('csv, tsv and txt', $message);
    }

    #[Test]
    public function reportsAllowedHostsAsString(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['allowedHosts'] = 'data.example.org,cdn.example.org';

        $messages = FileOptionsCheck::messages();

        self::assertCount(1, $messages);
        self::assertStringContainsString("['allowedHosts']", $messages[0]);
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function nonBooleanDataProvider(): iterable
    {
        yield 'string' => ['1'];
        yield 'integer' => [1];
        yield 'string true' => ['true'];
    }

    #[Test]
    #[DataProvider('nonBooleanDataProvider')]
    public function reportsAllowActiveContentTargetsThatIsNotBoolean(mixed $value): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['allowActiveContentTargets'] = $value;

        $messages = FileOptionsCheck::messages();

        self::assertCount(1, $messages);
        self::assertStringContainsString("['allowActiveContentTargets']", $messages[0]);
    }

    #[Test]
    public function reportsNothingForTheOptionsOfVersion4(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export'] = [
            'fileDirectories' => ['fileadmin/import'],
            'allowedHosts' => ['data.example.org'],
            'allowActiveContentTargets' => true,
            'yamlConfigurationDirectories' => [],
        ];

        self::assertSame([], FileOptionsCheck::messages());
    }

    #[Test]
    public function reportsNothingWithoutConfiguration(): void
    {
        self::assertSame([], FileOptionsCheck::messages());
    }

    #[Test]
    public function reportsEachOption(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export'] = [
            'fileSourceDirectories' => ['fileadmin/import'],
            'fileTargetDirectories' => ['fileadmin/export'],
            'fileTargetExtensions' => ['csv'],
            'allowedHosts' => 'data.example.org',
        ];

        self::assertCount(4, FileOptionsCheck::messages());
    }
}
