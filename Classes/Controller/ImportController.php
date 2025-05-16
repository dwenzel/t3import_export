<?php

declare(strict_types=1);
namespace CPSIT\T3importExport\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Core\Resource\Exception\InvalidConfigurationException;

/***************************************************************
 *  Copyright notice
 *  (c) 2015 Dirk Wenzel <dirk.wenzel@cps-it.de>
 *  All rights reserved
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

#[AsController]
class ImportController extends BaseController implements TransferControllerInterface
{
    final public const string SETTINGS_KEY = 'import';
    public const TEMPLATE_PATH = 'Import/Index';
    /**
     * Import task action
     *
     * @param string $identifier
     *
     * @throws InvalidConfigurationException
     */
    public function importTaskAction($identifier): ResponseInterface
    {
        $this->taskAction($identifier);
        $this->moduleTemplate->setContent($this->view->render());

        // this fails randomly since ModuleTemplate tries to access the
        // fe user session for flash message and a valid user seems to be missing.
        //return $this->htmlResponse($this->moduleTemplate->renderContent());
        return $this->htmlResponse();
    }

    /**
     * Import
     *
     * @param string $identifier
     *
     * @throws InvalidConfigurationException
     */
    public function importSetAction($identifier): ResponseInterface
    {
        $this->setAction($identifier);
        $this->moduleTemplate->setContent($this->view->render());

        // this fails randomly since ModuleTemplate tries to access the
        // fe user session for flash message and a valid user seems to be missing.
        //return $this->htmlResponse($this->moduleTemplate->renderContent());
        return $this->htmlResponse();
    }

    /**
     * Returns the settings key
     *
     * @return string
     */
    public function getSettingsKey()
    {
        return self::SETTINGS_KEY;
    }
}
