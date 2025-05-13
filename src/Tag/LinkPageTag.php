<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Tag;

use MediaWiki\Extension\ParserPower\LinkUtils;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

/**
 * Tag for replacing links with the name of the page they link to (<linkpage>).
 */
final class LinkPageTag extends ParserTag {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'linkpage';
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
				return $page;
			} );
		}

		return [ $text, 'markerType' => 'none' ];
	}
}
