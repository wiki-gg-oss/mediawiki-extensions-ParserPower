<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function;

use MediaWiki\Extension\ParserPower\LinkUtils;
use MediaWiki\Extension\ParserPower\ParameterParser;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

/**
 * Parser function for replacing links with their appropriate link text (#linktext).
 */
final class LinkTextFunction extends ParserFunctionBase {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'linktext';
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
	public function execute( Parser $parser, PPFrame $frame, ParameterParser $params ): string {
		return LinkUtils::replace( $params->get( 0 ), static function ( $page, $text ) {
			return $text ?? $page;
		} );
	}
}
