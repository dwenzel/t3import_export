<?php

namespace CPSIT\T3importExport\Tests\Unit\Component\PreProcessor;
use CPSIT\T3importExport\Component\PreProcessor\MapObjectToArray;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2018 Dirk Wenzel <wenzel@cps-it.de>
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

class MapObjectToArrayTest extends UnitTestCase
{
    /**
     * Subject under test
     * @var \CPSIT\T3importExport\Component\PreProcessor\MapObjectToArray
     */
    protected $subject;

    /**
     * Set up subject
     */
    public function setUp()
    {
        $this->subject = new \CPSIT\T3importExport\Component\PreProcessor\MapObjectToArray();
    }

    /**
     * @test
     */
    public function processMapsObjectToArray() {
        /** @var DomainObjectInterface $mockObject */
        $mockObject = $this->getMockBuilder(DomainObjectInterface::class)
            ->getMock();
        $configuration = [];
        $this->subject->process($configuration, $mockObject);
        $this->assertSame(
            [],
            $mockObject
        );
    }

    /**
     * provides data for field values from configuration
     */
    public function getFieldValueFromConfigurationReturnsExpectedValueDataProvider() {
        return [
            // configuration, key, default, expectedValue
            [[], '', '', ''],
            [
                [
                    \CPSIT\T3importExport\Component\PreProcessor\MapObjectToArray::CONFIGURATION_KEY_FIELDS => [
                        'foo' => 'bar'
                    ]
                ],
                'foo',
                '',
                'bar'
            ],
            [
                // empty value - returns default
                [
                    \CPSIT\T3importExport\Component\PreProcessor\MapObjectToArray::CONFIGURATION_KEY_FIELDS => [
                        'foo' => ''
                    ]
                ],
                'foo',
                'baz',
                'baz'
            ]

        ];
    }

    /**
     * @test
     * @dataProvider getFieldValueFromConfigurationReturnsExpectedValueDataProvider
     * @param array $configuration
     * @param string $key
     * @param mixed $default
     * @param mixed $expected
     */
    public function getFieldValueFromConfigurationReturnsExpectedValue(array $configuration, $key, $default, $expected) {
        $this->assertSame(
            $expected,
            $this->subject->getFieldValueFromConfiguration($configuration, $key, $default)
        );
    }

    /**
     * @test
     */
    public function getValueMapInitiallyReturnsClassConstant() {
        $this->assertSame(
            MapObjectToArray::ENTITY_VALUE_MAP,
            $this->subject->getValueMap()
        );
    }

    /**
     * @test
     */
    public function getValueMapResultCanBeOverridden() {
        $expectedMap = MapObjectToArray::ENTITY_VALUE_MAP;
        $override = ['foo' => 'bar'];

        ArrayUtility::mergeRecursiveWithOverrule(
            $expectedMap,
            $override
        );

        $this->assertSame(
            $expectedMap,
            $this->subject->getValueMap($override)
        );
    }


    /**
     * @test
     */
    public function getRequiredFieldsReturnsClassConstant() {
        $this->assertSame(
            \CPSIT\T3importExport\Component\PreProcessor\MapObjectToArray::REQUIRED_CONFIGURATION_FIELDS,
            $this->subject->getRequiredFields()
        );
    }

    /**
     * @test
     */
    public function processSetsRequiredFieldsFromConfiguration() {
        $requiredFields = ['foo'];
        $configuration = [
            \CPSIT\T3importExport\Component\PreProcessor\MapObjectToArray::CONFIGURATION_KEY_FIELDS => [
                'foo' => 'bar'
            ]
        ];
        /** @var DomainObjectInterface|\PHPUnit_Framework_MockObject_MockObject $mockRecord */
        $mockRecord = $this->getMockBuilder(DomainObjectInterface::class)->getMock();
        $this->subject = $this->getMockBuilder(MapObjectToArray::class)
            ->setMethods(['getRequiredFields'])
            ->getMock();

        // child classes may override class constant
        $this->subject->expects($this->once())
            ->method('getRequiredFields')
            ->will($this->returnValue($requiredFields));

        $expected = [
            'foo' => 'bar'
        ];

        $this->subject->process($configuration, $mockRecord);
        $this->assertEquals(
            $expected,
            $mockRecord
        );
    }

    /**
     * @test
     */
    public function processMapsFields() {
        $configuration = [
            'map' => [
                'foo' => 'bar'
            ],
        ];
        $barValue = 'baz';
        /** @var DomainObjectInterface|\PHPUnit_Framework_MockObject_MockObject $mockObject */
        $mockObject = $this->getMockBuilder(DomainObjectInterface::class)
            ->setMethods(['getBar'])->getMockForAbstractClass();
        $mockObject->expects($this->once())
            ->method('getBar')
            ->willReturn($barValue);

        $this->subject = $this->getMockBuilder(MapObjectToArray::class)
            ->setMethods(['dummy'])->getMock();

        $this->subject->process($configuration, $mockObject);
        $expected = [
            'foo' => $barValue
        ];

        $this->assertEquals(
            $expected,
            $mockObject
        );
    }
}
