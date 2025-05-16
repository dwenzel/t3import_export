<?php

declare(strict_types=1);
namespace CPSIT\T3importExport;

/**
 * Interface IdentifiableInterface
 */
interface IdentifiableInterface
{
    /**
     * Sets the identifier
     *
     * @param string $identifier
     * @return mixed
     */
    public function setIdentifier($identifier);

    /**
     * Gets the identifier
     *
     * @return string
     */
    public function getIdentifier();
}
