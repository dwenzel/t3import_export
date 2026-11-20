<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Functional\Persistence;

use CPSIT\ImportExportCore\File\DataSourceCSV;
use CPSIT\ImportExportCore\File\DataSourceXML;
use CPSIT\ImportExportCore\File\DataTargetCSV;
use CPSIT\ImportExportCore\Resource\DelegatingResourceLoader;
use CPSIT\ImportExportCore\Resource\HttpResourceLoader;
use CPSIT\ImportExportCore\Resource\PathResolverInterface;
use CPSIT\ImportExportCore\Resource\ResourceLoaderInterface;
use CPSIT\T3importExport\Persistence\DataSourceCSV as LegacyDataSourceCSV;
use CPSIT\T3importExport\Resource\Typo3PathResolver;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * File sources and targets of the core, created by TYPO3 with the path
 * resolver and resource loaders of the extension (#144)
 */
class FileComponentsTest extends FunctionalTestCase
{
    // typo3/cms-install is required by t3extension_tools
    protected array $coreExtensionsToLoad = ['install'];

    protected array $testExtensionsToLoad = [
        't3extension_tools',
        't3import_export',
    ];

    private const string FIXTURES = 'EXT:t3import_export/Tests/Functional/Fixtures/Files/';

    #[Test]
    public function servicesUseThePathResolverAndLoadersOfTheExtension(): void
    {
        self::assertInstanceOf(Typo3PathResolver::class, $this->get(PathResolverInterface::class));
        self::assertInstanceOf(DelegatingResourceLoader::class, $this->get(ResourceLoaderInterface::class));
        self::assertTrue($this->get(ResourceLoaderInterface::class)->supports('https://example.org/news.xml'));
        self::assertInstanceOf(HttpResourceLoader::class, $this->get(HttpResourceLoader::class));
    }

    #[Test]
    public function csvSourceReadsExtensionPath(): void
    {
        $source = GeneralUtility::makeInstance(DataSourceCSV::class);

        $records = iterator_to_array($source->getRecords(['file' => self::FIXTURES . 'courses.csv']), false);

        self::assertSame([['id' => '1', 'title' => 'First'], ['id' => '2', 'title' => 'Second']], $records);
    }

    #[Test]
    public function deprecatedCsvSourceReadsExtensionPath(): void
    {
        $source = GeneralUtility::makeInstance(LegacyDataSourceCSV::class);

        $records = @iterator_to_array($source->getRecords(['file' => self::FIXTURES . 'courses.csv']), false);

        self::assertCount(2, $records);
    }

    #[Test]
    public function xmlSourceReadsExtensionPath(): void
    {
        $source = GeneralUtility::makeInstance(DataSourceXML::class);

        $records = iterator_to_array(
            $source->getRecords(['file' => self::FIXTURES . 'courses.xml', 'element' => 'course']),
            false
        );

        self::assertSame(
            [
                ['@attributes' => ['id' => '1'], 'title' => 'First'],
                ['@attributes' => ['id' => '2'], 'title' => 'Second'],
            ],
            $records
        );
    }

    #[Test]
    public function csvTargetWritesFileRelativeToThePublicDirectory(): void
    {
        $target = GeneralUtility::makeInstance(DataTargetCSV::class);
        $configuration = ['file' => 'typo3temp/var/tests/courses.csv'];

        $target->persist(['id' => 1, 'title' => 'First'], $configuration);
        $target->persistAll(null, $configuration);

        $file = Environment::getPublicPath() . '/typo3temp/var/tests/courses.csv';
        self::assertSame("id,title\n1,First\n", file_get_contents($file));
        unlink($file);
    }

    #[Test]
    public function sourceRejectsPathsOutsideOfTheProject(): void
    {
        $source = GeneralUtility::makeInstance(DataSourceCSV::class);

        $this->expectException(\Exception::class);

        iterator_to_array($source->getRecords(['file' => '/etc/hosts']));
    }
}
