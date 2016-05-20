<?php
/**
 * Created by PhpStorm.
 * User: benjamin
 * Date: 19.05.16
 * Time: 15:53
 */

namespace CPSIT\T3importExport\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;


class DataStream extends AbstractEntity
{
    protected $context;

    /**
     * @return mixed
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param mixed $context
     */
    public function setContext($context)
    {
        $this->context = $context;
    }
}