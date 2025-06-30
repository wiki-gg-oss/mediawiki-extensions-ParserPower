<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Formatter;

/**
 * Base class for wikitext formatters, decoding a value from an expanded wikitext string.
 */
abstract class WikitextFormatter {

	/**
	 * Get the default decoded value.
	 * Typically, this corresponds to the value decoded from an empty or invalid text.
	 *
	 * @return mixed The default decoded value.
	 */
	public function getDefault() {
		return $this->format( '' );
	}

	/**
	 * Convert wikitext to a value.
	 *
	 * @param string $text Wikitext to convert.
	 * @return mixed The decoded value.
	 */
	abstract public function format( string $text );
}
