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
 * Parser function for retrieving a value from a list (#lstelem).
 */
final class LstElemFunction implements ParserFunction {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'lstelem';
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

		$inList = $params->get( 0 );

		if ( $inList === '' ) {
			return '';
		}

		$inSep = $params->get( 1 );
		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );
		$index = $params->get( 2 );
		$index = is_numeric( $index ) ? intval( $index ) : 1;

		$value = ListFunctions::arrayElement( ListFunctions::explodeList( $inSep, $inList ), $index );

		return ParserPower::evaluateUnescaped( $parser, $frame, $value );
	}
}
