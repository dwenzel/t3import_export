<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Functional\Component;

use CPSIT\T3importExport\Component\PreProcessor\SetFieldValue;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Components disabled by expressions, created as by the component factory (#77)
 */
class ExpressionConditionTest extends FunctionalTestCase
{
    // typo3/cms-install is required by t3extension_tools
    protected array $coreExtensionsToLoad = ['install'];

    protected array $testExtensionsToLoad = [
        't3extension_tools',
        't3import_export',
    ];

    #[Test]
    public function componentIsDisabledByExpression(): void
    {
        $subject = GeneralUtility::makeInstance(SetFieldValue::class);
        $configuration = [
            'disable' => [
                'expression' => "record['type'] == 'news'",
            ],
        ];

        self::assertTrue($subject->isDisabled($configuration, ['type' => 'news']));
        self::assertFalse($subject->isDisabled($configuration, ['type' => 'event']));
    }
}
