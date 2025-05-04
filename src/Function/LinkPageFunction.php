<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function;

use MediaWiki\Extension\ParserPower\ParserPower;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

/**
 * Parser function for replacing links with the name of the page they link to (#linkpage).
 */
final class LinkPageFunction implements ParserFunction {

	/**
	 * Replace links with the name of the page they link to.
	 *
	 * @param array $matches The parameters and values together, not yet exploded or trimmed.
	 * @return string The function output.
	 */
	private static function replace( array $matches ): string {
		$parts = explode( '|', $matches[1], 2 );
		return $parts[0];
	}

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'linkpage';
	}

	/**
	 * @inheritDoc
	 */
	public function render( Parser $parser, PPFrame $frame, array $params ): string {
		$text = ParserPower::expand( $frame, $params[0] ?? '' );
		return preg_replace_callback( '/\[\[(.*?)\]\]/', [ __CLASS__, 'replace' ], $text );
	}

	/**
	 * Perform the delinking operations for the linkpage tag.
	 *
	 * @param ?string $text The text within the tag function.
	 * @param array $attribs Attributes values of the tag function. Ignored.
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @return array The function output.
	 */
	public static function tagRender( ?string $text, array $attribs, Parser $parser, PPFrame $frame ): array {
		if ( $text === null ) {
			return [ '', 'markerType' => 'none' ];
		}

		$text = $parser->replaceVariables( $text, $frame );

		if ( $text !== '' ) {
			$text = preg_replace_callback( '/\[\[(.*?)\]\]/', [ __CLASS__, 'replace' ], $text );
		}

		return [ $text, 'markerType' => 'none' ];
	}
}
