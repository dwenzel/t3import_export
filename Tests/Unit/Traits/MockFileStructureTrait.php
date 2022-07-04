<?php

namespace CPSIT\T3importExport\Tests\Unit\Traits;

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
trait MockFileStructureTrait
{

    /**
     * @return array
     */
    protected function mockFileStructure(): array
    {
        $rootDirectory = 'root';

        $sourceFileContent = 'source file content';

        $sourceDirectory = 'sourceDir';
        $sourceFileName = 'foo.csv';
        $sourceFilePath = 'vfs://' . $rootDirectory . DIRECTORY_SEPARATOR . $sourceDirectory . DIRECTORY_SEPARATOR . $sourceFileName;
        $targetDirectory = 'targetDir';
        $configuration = [
            'targetDirectoryPath' => $targetDirectory
        ];

        $fileStructure = [
            $sourceDirectory => [
                $sourceFileName => $sourceFileContent
            ],
            $targetDirectory => []
        ];
        return array($rootDirectory, $sourceFileName, $sourceFilePath, $targetDirectory, $configuration, $fileStructure);
    }
}
