<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Formatter;

/**
 * Wikitext formatter for decoding an (int-based) enumeration value from wikitext.
 */
final class EnumFormatter extends WikitextFormatter {

	/**
	 * @param array $values Mapping of keywords to int values.
	 */
	public function __construct( private array $values ) {
	}

	/**
	 * @inheritDoc
	 */
	public function format( string $text, int $default = 0 ): int {
		$key = strtolower( $text );
		return $this->values[$key] ?? $default;
	}
}
