<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function;

use MediaWiki\Extension\ParserPower\LinkUtils;
use MediaWiki\Extension\ParserPower\Parameters;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

/**
 * Parser function for replacing links with the name of the page they link to (#linkpage).
 */
final class LinkPageFunction extends ParserFunctionBase {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'linkpage';
	}

	/**
	 * @inheritDoc
	 */
	public function getParamSpec(): array {
		return [
			0 => []
		];
	}

	/**
	 * @inheritDoc
	 */
	public function execute( Parser $parser, PPFrame $frame, Parameters $params ): string {
		return LinkUtils::replace( $params->get( 0 ), static function ( $page, $text ) {
			return $page;
		} );
	}
}
