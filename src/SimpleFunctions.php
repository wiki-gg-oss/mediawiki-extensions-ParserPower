<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower;

use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

final class SimpleFunctions {

	/**
	 * This function escapes all appropriate characters in the given text and returns the result.
	 *
	 * @param ?string $text The text within the tag function.
	 * @param array $attribs Attributes values of the tag function. Ignored.
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @return string The function output.
	 */
	public function escTagRender( ?string $text, array $attribs, Parser $parser, PPFrame $frame ): array {
		return [ ParserPower::escape( $text ), 'markerType' => 'none' ];
	}
}
