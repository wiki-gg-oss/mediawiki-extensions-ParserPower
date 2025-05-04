<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower;

use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

/**
 * Tag for escaping a value (<esc>, <esc1>, <esc2>, ...).
 */
final class EscTag {

	/**
	 * Gets the list of all available tag names.
	 *
	 * @return array The list of parser tag names.
	 */
	public function getNames(): array {
		$names = [ 'esc' ];
		for ( $index = 1; $index < 10; $index++ ) {
			$names[] = 'esc' . $index;
		}
		return $names;
	}

	/**
	 * This function escapes all appropriate characters in the given text and returns the result.
	 *
	 * @param ?string $text The text within the tag function.
	 * @param array $attribs Attributes values of the tag function. Ignored.
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @return string The function output.
	 */
	public function render( ?string $text, array $attribs, Parser $parser, PPFrame $frame ): array {
		return [ ParserPower::escape( $text ), 'markerType' => 'none' ];
	}
}
