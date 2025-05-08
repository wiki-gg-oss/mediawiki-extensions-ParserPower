<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function\List;

use MediaWiki\Extension\ParserPower\ListFunctions;
use MediaWiki\Extension\ParserPower\ParameterParser;
use MediaWiki\Extension\ParserPower\ParserPower;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;
use MediaWiki\Extension\ParserPower\Function\ParserFunction;

/**
 * Parser function for appending a value to a list (#lstapp).
 */
final class LstAppFunction implements ParserFunction {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'lstapp';
	}

	/**
	 * @inheritDoc
	 */
	public function render( Parser $parser, PPFrame $frame, array $params ): string {
		$params = new ParameterParser( $frame, $params, [
			ListFunctions::PARAM_OPTIONS['list'],
			ListFunctions::PARAM_OPTIONS['insep'],
			[ 'unescape' => true ]
		] );

		$list = $params->get( 0 );
		$value = $params->get( 2 );

		if ( $list === '' ) {
			return ParserPower::evaluateUnescaped( $parser, $frame, $value );
		}

		$sep = $params->get( 1 );
		$sep = $parser->getStripState()->unstripNoWiki( $sep );

		$values = ListFunctions::explodeList( $sep, $list );
		if ( $value !== '' ) {
			$values[] = $value;
		}
		return ParserPower::evaluateUnescaped( $parser, $frame, ListFunctions::implodeList( $values, $sep ) );
	}
}
