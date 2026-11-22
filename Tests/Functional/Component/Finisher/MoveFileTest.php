<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Functional\Component\Finisher;

use CPSIT\T3importExport\Component\Finisher\MoveFile;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Exception\ExistingTargetFileNameException;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

#[CoversClass(MoveFile::class)]
class MoveFileTest extends FunctionalTestCase
{
    // typo3/cms-install is required by t3extension_tools
    protected array $coreExtensionsToLoad = ['install'];

    protected array $testExtensionsToLoad = [
        't3extension_tools',
        't3import_export',
    ];

    protected MoveFile $subject;

    private string $basePath;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->basePath = Environment::getPublicPath() . '/fileadmin/';
        GeneralUtility::mkdir_deep($this->basePath . 'import/');
        file_put_contents($this->basePath . 'import/courses.csv', 'id,title');
        $this->get(StorageRepository::class)->createLocalStorage('fileadmin', 'fileadmin/', 'relative', '', true);
        $this->subject = GeneralUtility::makeInstance(MoveFile::class);
    }

    #[\Override]
    protected function tearDown(): void
    {
        GeneralUtility::rmdir($this->basePath, true);
        parent::tearDown();
    }

    private function move(array $configuration): bool
    {
        $records = [];
        $result = [];

        return $this->subject->process($configuration, $records, $result);
    }

    #[Test]
    public function processMovesFileIntoNewTargetDirectory(): void
    {
        $configuration = [
            'source' => ['name' => 'courses.csv', 'directory' => 'import/'],
            'target' => ['name' => 'courses-archived.csv', 'directory' => 'archive/'],
        ];

        self::assertTrue($this->subject->isConfigurationValid($configuration));
        self::assertTrue($this->move($configuration));

        self::assertFileDoesNotExist($this->basePath . 'import/courses.csv');
        self::assertStringEqualsFile($this->basePath . 'archive/courses-archived.csv', 'id,title');
    }

    #[Test]
    public function processMovesFileWithConfiguredStorages(): void
    {
        GeneralUtility::mkdir_deep($this->basePath . 'archive/');
        $configuration = [
            'source' => ['name' => 'courses.csv', 'directory' => 'import/', 'storage' => 1],
            'target' => ['name' => 'courses.csv', 'directory' => 'archive/', 'storage' => 1],
        ];

        self::assertTrue($this->move($configuration));

        self::assertFileExists($this->basePath . 'archive/courses.csv');
    }

    #[Test]
    public function processReturnsFalseIfSourceFileDoesNotExist(): void
    {
        $configuration = [
            'source' => ['name' => 'missing.csv', 'directory' => 'import/'],
            'target' => ['name' => 'courses.csv', 'directory' => 'archive/'],
        ];

        self::assertFalse($this->move($configuration));
        self::assertDirectoryDoesNotExist($this->basePath . 'archive/');
    }

    #[Test]
    public function processRenamesNewFileByDefault(): void
    {
        GeneralUtility::mkdir_deep($this->basePath . 'archive/');
        file_put_contents($this->basePath . 'archive/courses.csv', 'existing');
        $configuration = [
            'source' => ['name' => 'courses.csv', 'directory' => 'import/'],
            'target' => ['name' => 'courses.csv', 'directory' => 'archive/'],
        ];

        self::assertTrue($this->move($configuration));

        self::assertStringEqualsFile($this->basePath . 'archive/courses.csv', 'existing');
        self::assertStringEqualsFile($this->basePath . 'archive/courses_01.csv', 'id,title');
    }

    #[Test]
    public function processReplacesExistingFileIfConfigured(): void
    {
        GeneralUtility::mkdir_deep($this->basePath . 'archive/');
        file_put_contents($this->basePath . 'archive/courses.csv', 'existing');
        $configuration = [
            'source' => ['name' => 'courses.csv', 'directory' => 'import/'],
            'target' => [
                'name' => 'courses.csv',
                'directory' => 'archive/',
                'conflictMode' => MoveFile::CONFLICT_MODE_OVERRIDE_EXISTING_FILE,
            ],
        ];

        self::assertTrue($this->move($configuration));

        self::assertStringEqualsFile($this->basePath . 'archive/courses.csv', 'id,title');
    }

    #[Test]
    public function processCancelsIfConfiguredAndTargetFileExists(): void
    {
        GeneralUtility::mkdir_deep($this->basePath . 'archive/');
        file_put_contents($this->basePath . 'archive/courses.csv', 'existing');
        $configuration = [
            'source' => ['name' => 'courses.csv', 'directory' => 'import/'],
            'target' => [
                'name' => 'courses.csv',
                'directory' => 'archive/',
                'conflictMode' => MoveFile::CONFLICT_MODE_CANCEL,
            ],
        ];

        $this->expectException(ExistingTargetFileNameException::class);

        $this->move($configuration);
    }
}
