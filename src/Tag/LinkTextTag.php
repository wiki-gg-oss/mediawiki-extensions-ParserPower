<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Tag;

use MediaWiki\Extension\ParserPower\LinkUtils;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

/**
 * Tag for replacing links with their appropriate link text (<linktext>).
 */
final class LinkTextTag extends ParserTag {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'linktext';
	}

	/**
	 * @inheritDoc
	 */
	public function render( ?string $text, array $attribs, Parser $parser, PPFrame $frame ): array {
		if ( $text === null ) {
			return [ '', 'markerType' => 'none' ];
		}

		$text = $parser->replaceVariables( $text, $frame );

		if ( $text !== '' ) {
			$text = LinkUtils::replace( $text, static function ( $page, $text ) {
				return $text ?? $page;
			} );
		}

		return [ $text, 'markerType' => 'none' ];
	}
}
