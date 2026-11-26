<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Functional\Persistence;

use CPSIT\ImportExportCore\Exception\MissingResourceException;
use CPSIT\ImportExportCore\Exception\PersistenceException;
use CPSIT\ImportExportCore\File\DataSourceCSV;
use CPSIT\ImportExportCore\File\DataSourceXML;
use CPSIT\ImportExportCore\File\DataTargetCSV;
use CPSIT\ImportExportCore\Resource\DelegatingResourceLoader;
use CPSIT\ImportExportCore\Resource\HttpResourceLoader;
use CPSIT\ImportExportCore\Resource\PathResolverInterface;
use CPSIT\ImportExportCore\Resource\ResourceLoaderInterface;
use CPSIT\T3importExport\Persistence\DataSourceCSV as LegacyDataSourceCSV;
use CPSIT\T3importExport\Persistence\DataTargetCSV as LegacyDataTargetCSV;
use CPSIT\T3importExport\Resource\Typo3PathResolver;
use PHPUnit\Framework\Attributes\DataProvider;
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
    public function csvTargetWritesFileInTheImportDirectory(): void
    {
        $target = GeneralUtility::makeInstance(DataTargetCSV::class);
        $file = Environment::getVarPath() . '/import/tests/courses.csv';
        $configuration = ['file' => $file];

        $target->persist(['id' => 1, 'title' => 'First'], $configuration);
        $target->persistAll(null, $configuration);

        self::assertSame("id,title\n1,First\n", file_get_contents($file));
        unlink($file);
    }

    #[Test]
    public function csvTargetDoesNotWriteIntoExtensions(): void
    {
        $target = GeneralUtility::makeInstance(DataTargetCSV::class);

        $this->expectException(PersistenceException::class);
        $this->expectExceptionCode(DataTargetCSV::ERROR_WRITE_FAILED);

        $target->persist(['id' => 1], ['file' => self::FIXTURES . 'written.csv']);
    }

    #[Test]
    public function csvTargetDoesNotWriteIntoThePublicDirectory(): void
    {
        $target = GeneralUtility::makeInstance(DataTargetCSV::class);

        $this->expectException(PersistenceException::class);

        $target->persist(['id' => 1], ['file' => 'typo3temp/courses.csv']);
    }

    /**
     * @return iterable<string, array{class-string, string}>
     */
    public static function notAllowedCsvTargetNameDataProvider(): iterable
    {
        foreach (['core' => DataTargetCSV::class, 'deprecated' => LegacyDataTargetCSV::class] as $component => $class) {
            yield $component . ', question mark' => [$class, 'a?b.csv'];
            yield $component . ', hash' => [$class, 'report#1.csv'];
            yield $component . ', colon' => [$class, 'a:b.csv'];
        }
    }

    /**
     * @param class-string<DataTargetCSV> $class
     */
    #[Test]
    #[DataProvider('notAllowedCsvTargetNameDataProvider')]
    public function csvTargetDoesNotWriteFileNamesThatAreNotAllowed(string $class, string $fileName): void
    {
        $target = GeneralUtility::makeInstance($class);
        $file = Environment::getVarPath() . '/import/tests/' . $fileName;

        try {
            @$target->persist(['id' => 1], ['file' => $file]);
            self::fail('The file is written.');
        } catch (PersistenceException $exception) {
            self::assertSame(DataTargetCSV::ERROR_WRITE_FAILED, $exception->getCode());
            self::assertSame(Typo3PathResolver::ERROR_FILE_NAME_NOT_ALLOWED, $exception->getPrevious()?->getCode());
        }
        self::assertFileDoesNotExist($file);
    }

    #[Test]
    public function urlsAreNotLoadedWithoutAllowedHosts(): void
    {
        $source = GeneralUtility::makeInstance(DataSourceCSV::class);

        $this->expectException(MissingResourceException::class);
        $this->expectExceptionCode(HttpResourceLoader::ERROR_HOST_NOT_ALLOWED);

        iterator_to_array($source->getRecords(['url' => 'https://example.org/courses.csv']));
    }

    #[Test]
    public function sourceRejectsPathsOutsideOfTheProject(): void
    {
        $source = GeneralUtility::makeInstance(DataSourceCSV::class);

        $this->expectException(MissingResourceException::class);
        $this->expectExceptionCode(Typo3PathResolver::ERROR_NOT_ALLOWED);

        iterator_to_array($source->getRecords(['file' => '/etc/hosts.csv']));
    }
}
