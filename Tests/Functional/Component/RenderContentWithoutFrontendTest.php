<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Functional\Component;

use CPSIT\T3importExport\Component\PreProcessor\ConcatenateFields;
use CPSIT\T3importExport\Component\PreProcessor\RenderContent;
use CPSIT\T3importExport\Component\PreProcessor\SetFieldValue;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Content rendering as on the command line: no request, no TSFE (#64)
 */
class RenderContentWithoutFrontendTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        't3import_export',
    ];

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        unset($GLOBALS['TYPO3_REQUEST'], $GLOBALS['TSFE']);
    }

    #[Test]
    public function renderContentRendersTextObjectWithFieldFallback(): void
    {
        $subject = $this->get(RenderContent::class);
        $configuration = [
            'fields' => [
                'teaser' => [
                    '_typoScriptNodeValue' => 'TEXT',
                    'field' => 'teaser // abstract',
                ],
            ],
        ];
        $record = ['teaser' => '', 'abstract' => 'from abstract'];

        $subject->process($configuration, $record);

        self::assertSame('from abstract', $record['teaser']);
    }

    #[Test]
    public function renderContentRendersContentObjectArray(): void
    {
        $subject = $this->get(RenderContent::class);
        $configuration = [
            'fields' => [
                'title' => [
                    '_typoScriptNodeValue' => 'COA',
                    '10' => [
                        '_typoScriptNodeValue' => 'TEXT',
                        'field' => 'title',
                        'case' => 'upper',
                    ],
                    '20' => [
                        '_typoScriptNodeValue' => 'TEXT',
                        'field' => 'year',
                        'noTrimWrap' => '| (|)|',
                    ],
                ],
            ],
        ];
        $record = ['title' => 'Annual report', 'year' => '2026'];

        $subject->process($configuration, $record);

        self::assertSame('ANNUAL REPORT (2026)', $record['title']);
    }

    #[Test]
    public function componentIsDisabledByRenderedCondition(): void
    {
        $subject = GeneralUtility::makeInstance(SetFieldValue::class);
        $configuration = [
            'disable' => [
                '_typoScriptNodeValue' => 'TEXT',
                'value' => '1',
            ],
        ];

        self::assertTrue($subject->isDisabled($configuration, ['uid' => 1]));
    }

    #[Test]
    public function componentIsNotDisabledByRenderedCondition(): void
    {
        $subject = GeneralUtility::makeInstance(SetFieldValue::class);
        $configuration = [
            'disable' => [
                '_typoScriptNodeValue' => 'TEXT',
                'field' => 'hidden',
            ],
        ];

        self::assertFalse($subject->isDisabled($configuration, ['uid' => 1, 'hidden' => '0']));
    }

    #[Test]
    public function concatenateFieldsWrapsValues(): void
    {
        $subject = new ConcatenateFields();
        $configuration = [
            'targetField' => 'name',
            'fields' => [
                'first' => ['noTrimWrap' => '||, |'],
                'last' => [],
            ],
        ];
        $record = ['first' => 'Ada', 'last' => 'Lovelace', 'name' => ''];

        $subject->process($configuration, $record);

        self::assertSame('Ada, Lovelace', $record['name']);
    }
}
