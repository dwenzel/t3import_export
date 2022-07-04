<?php

namespace CPSIT\T3importExport\Tests\Unit\Component\Initializer;

use CPSIT\T3importExport\Component\Initializer\AbstractInitializer;
use PHPUnit\Framework\TestCase;
use ReflectionException;

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

/**
 * Class AbstractInitializerTest
 *
 * @package CPSIT\T3importExport\Tests\Unit\Component\Initializer
 * @coversDefaultClass \CPSIT\T3importExport\Component\Initializer\AbstractInitializer
 */
class AbstractInitializerTest extends TestCase
{

    /**
     * @var AbstractInitializer
     */
    protected AbstractInitializer $subject;

    /**
     * set up
     * @throws ReflectionException
     * @noinspection ReturnTypeCanBeDeclaredInspection
     */
    public function setUp()
    {
        $this->subject = $this->getMockForAbstractClass(AbstractInitializer::class);
    }

    public function testIsConfigurationValidInitiallyReturnsTrue(): void
    {
        $this->assertTrue(
            $this->subject->isConfigurationValid([])
        );
    }
}
