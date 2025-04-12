<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Operation;

/**
 * List value operation that checks whether the list value is in a list.
 */
final class ListInclusionOperation implements WikitextOperation {

	/**
	 * Array with values to look for as keys.
	 *
	 * @var array
	 */
	private array $valueSet;

	/**
	 * @param array $values Values to look for.
	 * @param string $resultWhenIn Variable-free wikitext to return when the list value is included.
	 * @param string $resultWhenOut Variable-free wikitext to return when the list value is not included.
	 * @param bool $caseSensitive True if case sentitive, false otherwise.
	 */
	public function __construct(
		array $values,
		private string $resultWhenIn,
		private string $resultWhenOut = '',
		private bool $caseSensitive = true
	) {
		if ( $caseSensitive ) {
			foreach ( $values as $value ) {
				$this->valueSet[$value] = true;
			}
		} else {
			foreach ( $values as $value ) {
				$this->valueSet[strtolower( $value )] = true;
			}
		}
	}

	/**
	 * @inheritDoc
	 */
	public function apply( array $fields, ?int $index = null ): string {
		$value = $fields[0];
		if ( !$this->caseSensitive ) {
			$value = strtolower( $value );
		}

		if ( isset( $this->valueSet[$value] ) ) {
			return $this->resultWhenIn;
		} else {
			return $this->resultWhenOut;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getFieldLimit(): ?int {
		return 1;
	}
}
