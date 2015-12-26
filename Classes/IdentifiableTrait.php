<?php
namespace CPSIT\T3import;

/**
 * Interface IdentifiableInterface
 *
 * @package CPSIT\T3import\Persistence\Factory
 */
trait IdentifiableTrait {

	/**
	 * Unique identifier
	 *
	 * @var string
	 */
	protected $identifier;

	/**
	 * Sets the identifier
	 *
	 * @param string $identifier
	 * @return mixed
	 */
	public function setIdentifier($identifier) {
		$this->identifier = $identifier;
	}

	/**
	 * Gets the identifier
	 *
	 * @return string
	 */
	public function getIdentifier() {
		return $this->identifier;
	}
}