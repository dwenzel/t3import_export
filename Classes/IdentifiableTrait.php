<?php
namespace CPSIT\T3importExport;

/**
 * Interface IdentifiableInterface
 *
 * @package CPSIT\T3importExport\Persistence\Factory
 */
trait IdentifiableTrait
{

    /**
     * Unique identifier
     *
     * @var ?string
     */
    protected ?string $identifier = null;

    /**
     * Sets the identifier
     *
     * @param string $identifier
     * @return mixed
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * Gets the identifier
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }
}
