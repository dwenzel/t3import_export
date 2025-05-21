<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit;

use CPSIT\ImportExportCore\ConfigurableTrait;
use CPSIT\ImportExportCore\Exception\InvalidConfigurationException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2015 Dirk Wenzel <dirk.wenzel@cps-it.de>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
class ConfigurableTraitTest extends TestCase
{
    protected object $subject;

    protected function setUp(): void
    {
        $this->subject = new class() {
            use ConfigurableTrait;

            public function isConfigurationValid(array $configuration): bool
            {
                return true;
            }
        };
    }

    #[Test]
    public function testGetConfigurationInitiallyReturnsEmptyArray(): void
    {
        $expected = [];
        $this->assertSame(
            $expected,
            $this->subject->getConfiguration()
        );
    }

    #[Test]
    public function testSetConfigurationSetsValidConfiguration(): void
    {
        $configuration = ['foo'];

        $this->subject->setConfiguration($configuration);

        $this->assertSame(
            $configuration,
            $this->subject->getConfiguration()
        );
    }

    #[Test]
    public function testSetConfigurationThrowsExceptionForInvalidConfiguration(): void
    {
        // set up a test subject which validates any configuration as false
        $this->subject = new class() {
            use ConfigurableTrait;

            public function isConfigurationValid(array $configuration): bool
            {
                return false;
            }
        };

        $this->expectExceptionCode(1_451_659_793);
        $this->expectException(InvalidConfigurationException::class);
        $configuration = ['foo'];

        $this->subject->setConfiguration($configuration);
    }
}
