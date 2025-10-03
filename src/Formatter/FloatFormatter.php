<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Formatter;

/**
 * Wikitext formatter for decoding a floating-point number from wikitext.
 */
final class FloatFormatter extends WikitextFormatter {

	/**
	 * @param ?float $min Lower bound.
	 */
	public function __construct( private ?float $min = null ) {
	}

	/**
	 * @inheritDoc
	 */
	public function format( string $text, float $default = 0 ): float {
		$value = is_numeric( $text ) ? floatval( $text ) : $default;
		if ( $this->min !== null ) {
			$value = max( $value, $this->min );
		}
		return $value;
	}
}
