<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Formatter;

/**
 * Wikitext formatter for decoding a set of flags from wikitext.
 */
final class FlagsFormatter extends WikitextFormatter {

	/**
	 * @param array $flags Mapping of keywords to a set of flags to enable (`include`) or disable (`exclude`).
	 */
	public function __construct( private array $flags ) {
	}

	/**
	 * @inheritDoc
	 */
	public function format( string $text, int $default = 0 ): int {
		$keys = strtolower( $text );
		$value = $default;
		foreach ( explode( ' ', $keys ) as $key ) {
			$flags = $this->flags[$key] ?? [];
			if ( isset( $flags['include'] ) ) {
				$value |= $flags['include'];
			}
			if ( isset( $flags['exclude'] ) ) {
				$value &= ~$flags['exclude'];
			}
		}
		return $value;
	}
}
