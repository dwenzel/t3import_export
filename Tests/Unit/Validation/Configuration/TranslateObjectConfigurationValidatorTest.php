<?php

namespace CPSIT\T3importExport\Tests\Validation\Configuration;

use CPSIT\T3importExport\Validation\Configuration\MappingConfigurationValidator;
use CPSIT\T3importExport\Validation\Configuration\TargetClassConfigurationValidator;
use CPSIT\T3importExport\Validation\Configuration\TranslateObjectConfigurationValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2022 Dirk Wenzel <wenzel@cps-it.de>
 *  All rights reserved
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * A copy is found in the text file GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
class TranslateObjectConfigurationValidatorTest extends TestCase
{
    protected TranslateObjectConfigurationValidator $subject;

    protected const MINIMAL_VALID_CONFIG = [
        TranslateObjectConfigurationValidator::KEY_PARENT_FIELD => 'oof',
        TranslateObjectConfigurationValidator::KEY_LANGUAGE => 0
    ];

    /**
     * @var TargetClassConfigurationValidator|MockObject
     */
    protected TargetClassConfigurationValidator $targetClassConfigurationValidator;
    protected MappingConfigurationValidator $mappingConfigurationValidator;

    protected function setUp(): void
    {
        $this->targetClassConfigurationValidator = $this->getMockBuilder(
            TargetClassConfigurationValidator::class
        )
            ->onlyMethods(['isValid'])
            ->getMock();
        $this->mappingConfigurationValidator = $this->getMockBuilder(
            MappingConfigurationValidator::class
        )
            ->onlyMethods(['isValid'])
            ->getMock();
        $this->subject = new TranslateObjectConfigurationValidator(
            $this->targetClassConfigurationValidator,
            $this->mappingConfigurationValidator
        );
    }

    #[Test]
    #[DataProvider('validConfigurationDataProvider')]
    public function testValidateReturnsTrueForValidConfiguration(array $configuration): void
    {
        self::assertTrue(
            $this->subject->isValid($configuration)
        );
    }

    public static function validConfigurationDataProvider(): array
    {
        return [
            'minimal - w/o mapping' => [
                self::MINIMAL_VALID_CONFIG
            ],
            'mapping set, empty mapping config' => [
                [
                    TranslateObjectConfigurationValidator::KEY_PARENT_FIELD => 'bar',
                    TranslateObjectConfigurationValidator::KEY_LANGUAGE => 9,
                    TranslateObjectConfigurationValidator::KEY_MAPPING => []
                ]
            ]
        ];
    }

    #[Test]
    #[DataProvider('invalidConfigurationDataProvider')]
    public function testValidateReturnsFalseForInvalidConfiguration(array $configuration): void
    {
        self::assertFalse(
            $this->subject->isValid($configuration)
        );
    }


    public static function invalidConfigurationDataProvider(): array
    {
        return [
            'empty config' => [
                []
            ],

            'language missing' => [
                [TranslateObjectConfigurationValidator::KEY_PARENT_FIELD => 'bar']
            ],
            'parentField missing' => [
                [
                    TranslateObjectConfigurationValidator::KEY_LANGUAGE => 8
                ]
            ]
        ];
    }

    #[Test]
    public function testIsConfigurationValidReturnsTrueForValidTargetClassConfiguration(): void
    {
        $config = self::MINIMAL_VALID_CONFIG;
        $validClass = 'FooBar';
        $mappingConfiguration = [
            TranslateObjectConfigurationValidator::KEY_TARGET_CLASS => $validClass
        ];
        $config[TranslateObjectConfigurationValidator::KEY_MAPPING] = $mappingConfiguration;
        $this->targetClassConfigurationValidator->expects($this->once())
            ->method('isValid')
            ->with(...[$mappingConfiguration])
            ->willReturn(true);

        $this->subject->isValid($config);
    }

    #[Test]
    public function testIsConfigurationValidReturnsFalseForInvalidTargetClassConfiguration(): void
    {
        $config = self::MINIMAL_VALID_CONFIG;
        $validClass = 'FooBar';
        $mappingConfiguration = [
            TranslateObjectConfigurationValidator::KEY_TARGET_CLASS => $validClass
        ];
        $config[TranslateObjectConfigurationValidator::KEY_MAPPING] = $mappingConfiguration;
        $this->targetClassConfigurationValidator->expects($this->once())
            ->method('isValid')
            ->with(...[$mappingConfiguration])
            ->willReturn(false);

        $this->subject->isValid($config);
    }
    #[Test]
    public function testIsConfigurationValidReturnsTrueForValidMappingConfiguration(): void
    {
        $config = self::MINIMAL_VALID_CONFIG;
        $valid = 'FooBar';
        $mappingConfiguration = [
            TranslateObjectConfigurationValidator::KEY_CONFIG => $valid
        ];
        $config[TranslateObjectConfigurationValidator::KEY_MAPPING] = $mappingConfiguration;
        $this->mappingConfigurationValidator->expects($this->once())
            ->method('isValid')
            ->with(...[$mappingConfiguration])
            ->willReturn(true);

        $this->subject->isValid($config);
    }

    #[Test]
    public function testIsConfigurationValidReturnsFalseForInvalidMappingConfiguration(): void
    {
        $config = self::MINIMAL_VALID_CONFIG;
        $validConfig = ['FooBar'];
        $mappingConfiguration = [
            TranslateObjectConfigurationValidator::KEY_CONFIG => $validConfig
        ];
        $config[TranslateObjectConfigurationValidator::KEY_MAPPING] = $mappingConfiguration;
        $this->mappingConfigurationValidator->expects($this->once())
            ->method('isValid')
            ->with(...[$mappingConfiguration])
            ->willReturn(false);

        $this->subject->isValid($config);
    }
}
