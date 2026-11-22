<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Functional\Component\PostProcessor;

use CPSIT\T3importExport\Component\PostProcessor\SetL10nParent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

#[CoversClass(SetL10nParent::class)]
class SetL10nParentTest extends FunctionalTestCase
{
    // typo3/cms-install is required by t3extension_tools
    protected array $coreExtensionsToLoad = ['install'];

    protected array $testExtensionsToLoad = [
        't3extension_tools',
        't3import_export',
    ];

    protected SetL10nParent $subject;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/pages.csv');
        $this->subject = $this->get(SetL10nParent::class);
    }

    private function configuration(array $parent = [], array $additional = []): array
    {
        return array_replace(
            [
                'subject' => ['parentField' => 'parentTitle'],
                'parent' => array_replace(
                    ['table' => 'pages', 'matchField' => 'title', 'identityField' => 'uid'],
                    $parent
                ),
            ],
            $additional
        );
    }

    #[Test]
    public function isConfigurationValidUsesValidator(): void
    {
        self::assertTrue($this->subject->isConfigurationValid($this->configuration()));
        self::assertFalse($this->subject->isConfigurationValid(['subject' => ['parentField' => 'foo']]));
    }

    #[Test]
    public function processReturnsFalseIfParentFieldIsEmpty(): void
    {
        $convertedRecord = ['title' => 'Translation'];
        $record = [];

        self::assertFalse($this->subject->process($this->configuration(), $convertedRecord, $record));
        self::assertArrayNotHasKey('l10n_parent', $convertedRecord);
    }

    #[Test]
    public function processReturnsFalseIfParentIsNotFound(): void
    {
        $convertedRecord = ['parentTitle' => 'Unknown page'];
        $record = [];

        self::assertFalse($this->subject->process($this->configuration(), $convertedRecord, $record));
        self::assertArrayNotHasKey('l10n_parent', $convertedRecord);
    }

    #[Test]
    public function processSetsL10nParent(): void
    {
        $convertedRecord = ['parentTitle' => 'Subpage 1'];
        $record = [];

        self::assertTrue($this->subject->process($this->configuration(), $convertedRecord, $record));
        self::assertSame(2, $convertedRecord['l10n_parent']);
        self::assertArrayNotHasKey('l10n_source', $convertedRecord);
    }

    #[Test]
    public function processPrefixesMatchValueAndSetsL10nSource(): void
    {
        $convertedRecord = ['parentTitle' => 'page 2'];
        $record = [];
        $configuration = $this->configuration(['prefix' => 'Sub'], ['setL10nSource' => 1]);

        self::assertTrue($this->subject->process($configuration, $convertedRecord, $record));
        self::assertSame(3, $convertedRecord['l10n_parent']);
        self::assertSame(3, $convertedRecord['l10n_source']);
    }

    #[Test]
    public function processMatchesIntegerValues(): void
    {
        $convertedRecord = ['parentTitle' => 3];
        $record = [];
        $configuration = $this->configuration(['matchField' => 'uid', 'identityField' => 'pid']);

        self::assertTrue($this->subject->process($configuration, $convertedRecord, $record));
        self::assertSame(1, $convertedRecord['l10n_parent']);
    }

    #[Test]
    public function processUsesUidAsDefaultIdentityField(): void
    {
        $convertedRecord = ['parentTitle' => 'Subpage 1'];
        $record = [];
        $configuration = $this->configuration();
        unset($configuration['parent']['identityField']);

        self::assertTrue($this->subject->process($configuration, $convertedRecord, $record));
        self::assertSame(2, $convertedRecord['l10n_parent']);
    }
}
