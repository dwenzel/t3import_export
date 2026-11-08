<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Command;

use CPSIT\ImportExportCore\Configuration\ConfigurationHandlerInterface;
use CPSIT\T3importExport\Command\ExportSetCommand;
use CPSIT\T3importExport\Command\Option\YamlConfigFileOption;
use CPSIT\T3importExport\Domain\Factory\TransferSetFactory;
use CPSIT\T3importExport\Service\DataTransferProcessor;
use PHPUnit\Framework\Attributes\Test;
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
class ExportSetCommandTest extends TestCase
{
    #[Test]
    public function yamlConfigOptionIsRegistered(): void
    {
        $subject = new ExportSetCommand(
            $this->createMock(TransferSetFactory::class),
            $this->createMock(DataTransferProcessor::class),
            $this->createMock(ConfigurationHandlerInterface::class)
        );

        $definition = $subject->getDefinition();

        self::assertTrue($definition->hasOption(YamlConfigFileOption::NAME));
        self::assertTrue($definition->hasShortcut(YamlConfigFileOption::SHORTCUT));
    }
}
