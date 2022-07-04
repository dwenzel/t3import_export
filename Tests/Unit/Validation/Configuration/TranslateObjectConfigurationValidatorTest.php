<?php

namespace CPSIT\T3importExport\Tests\Validation\Configuration;

use CPSIT\T3importExport\Validation\Configuration\MappingConfigurationValidator;
use CPSIT\T3importExport\Validation\Configuration\TargetClassConfigurationValidator;
use CPSIT\T3importExport\Validation\Configuration\TranslateObjectConfigurationValidator;
use PHPUnit\Framework\MockObject\MockObject;

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
class TranslateObjectConfigurationValidatorTest extends \PHPUnit\Framework\TestCase
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

    public function setUp()
    {
        $this->targetClassConfigurationValidator = $this->getMockBuilder(
            TargetClassConfigurationValidator::class
        )
            ->setMethods(['isValid'])
            ->getMock();
        $this->mappingConfigurationValidator = $this->getMockBuilder(
            MappingConfigurationValidator::class
        )
            ->setMethods(['isValid'])
            ->getMock();
        $this->subject = new TranslateObjectConfigurationValidator(
            $this->targetClassConfigurationValidator,
            $this->mappingConfigurationValidator
        );
    }

    /**
     * @param array $configuration
     * @dataProvider validConfigurationDataProvider
     */
    public function testValidateReturnsTrueForValidConfiguration(array $configuration): void
    {
        self::assertTrue(
            $this->subject->isValid($configuration)
        );
    }

    public function validConfigurationDataProvider(): array
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

    /**
     * @param array $configuration
     * @dataProvider invalidConfigurationDataProvider
     */
    public function testValidateReturnsFalseForValidConfiguration(array $configuration): void
    {
        self::assertFalse(
            $this->subject->isValid($configuration)
        );
    }


    public function invalidConfigurationDataProvider(): array
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
