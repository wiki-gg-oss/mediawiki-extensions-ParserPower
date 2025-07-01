<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Formatter;

/**
 * Wikitext formatter for decoding an integer from wikitext.
 */
final class IntFormatter extends WikitextFormatter {

	/**
	 * @param ?int $min Lower bound.
	 */
	public function __construct( private ?int $min = null ) {
	}

	/**
	 * @inheritDoc
	 */
	public function format( string $text, int $default = 0 ): int {
		$value = is_numeric( $text ) ? intval( $text ) : $default;
		if ( $this->min !== null ) {
			$value = max( $value, $this->min );
		}
		return $value;
	}
}
