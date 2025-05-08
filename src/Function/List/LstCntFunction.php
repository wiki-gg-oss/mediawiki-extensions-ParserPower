<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function\List;

use MediaWiki\Extension\ParserPower\ListFunctions;
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
			ListFunctions::PARAM_OPTIONS['list'],
			ListFunctions::PARAM_OPTIONS['insep']
		] );

		$list = $params->get( 0 );
		if ( $list === '' ) {
			return '0';
		}

		$sep = $params->get( 1 );
		$sep = $parser->getStripState()->unstripNoWiki( $sep );

		return (string)count( ListFunctions::explodeList( $sep, $list ) );
	}
}
