<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function\List;

use MediaWiki\Extension\ParserPower\ListUtils;
use MediaWiki\Extension\ParserPower\ParameterParser;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;
use MediaWiki\Extension\ParserPower\Function\ParserFunction;

/**
 * Parser function for counting values of a list (#lstcnt).
 */
final class LstCntFunction implements ParserFunction {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'lstcnt';
	}

	/**
	 * @inheritDoc
	 */
	public function render( Parser $parser, PPFrame $frame, array $params ): string {
		$params = new ParameterParser( $frame, $params, [
			ListUtils::PARAM_OPTIONS['list'],
			ListUtils::PARAM_OPTIONS['insep']
		] );

		$list = $params->get( 0 );
		if ( $list === '' ) {
			return '0';
		}

		$sep = $params->get( 1 );
		$sep = $parser->getStripState()->unstripNoWiki( $sep );

		return (string)count( ListUtils::explode( $sep, $list ) );
	}
}
