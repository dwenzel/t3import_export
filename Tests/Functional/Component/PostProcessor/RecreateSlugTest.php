<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Functional\Component\PostProcessor;

use CPSIT\T3importExport\Component\PostProcessor\RecreateSlug;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Configuration\SiteWriter;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

#[CoversClass(RecreateSlug::class)]
class RecreateSlugTest extends FunctionalTestCase
{
    // typo3/cms-install is required by t3extension_tools
    protected array $coreExtensionsToLoad = ['install'];

    protected array $testExtensionsToLoad = [
        't3extension_tools',
        't3import_export',
    ];

    protected RecreateSlug $subject;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/pages.csv');
        $this->subject = new RecreateSlug();
    }

    #[Test]
    public function processGeneratesSlugFromTitle(): void
    {
        $convertedRecord = ['uid' => 4, 'pid' => 1, 'title' => 'New Page'];
        $record = [];

        self::assertTrue($this->subject->process([], $convertedRecord, $record));
        self::assertSame('/new-page', $convertedRecord['slug']);
    }

    #[Test]
    public function processGeneratesUniqueSlugInSite(): void
    {
        $this->get(SiteWriter::class)->createNewBasicSite('main', 1, 'https://example.org/');
        // new pages are resolved to the site of their parent page
        $convertedRecord = ['uid' => 'NEW1', 'pid' => 1, 'title' => 'Subpage 1'];
        $record = [];

        $this->subject->process([], $convertedRecord, $record);

        self::assertSame('/subpage-1-1', $convertedRecord['slug']);
    }

    #[Test]
    public function processUsesConfiguredTableAndSlugField(): void
    {
        $GLOBALS['TCA']['pages']['columns']['path_segment'] = $GLOBALS['TCA']['pages']['columns']['slug'];
        $convertedRecord = ['uid' => 4, 'pid' => 1, 'title' => 'Other Page'];
        $record = [];
        $configuration = [
            RecreateSlug::KEY_TABLE_NAME => 'pages',
            RecreateSlug::KEY_SLUG_FIELD => 'path_segment',
        ];

        $this->subject->process($configuration, $convertedRecord, $record);

        // the slug of the parent page is only prepended to the field "slug" of pages
        self::assertSame('other-page', $convertedRecord['path_segment']);
        self::assertArrayNotHasKey('slug', $convertedRecord);
    }

    #[Test]
    public function processThrowsExceptionForFieldWithoutTca(): void
    {
        $convertedRecord = ['uid' => 4, 'pid' => 1, 'title' => 'New Page'];
        $record = [];

        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1570026984);

        $this->subject->process([RecreateSlug::KEY_SLUG_FIELD => 'unknown'], $convertedRecord, $record);
    }
}
