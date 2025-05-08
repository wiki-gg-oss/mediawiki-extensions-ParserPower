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
 * Parser function for joining two lists (#lstjoin).
 */
final class LstJoinFunction implements ParserFunction {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'lstjoin';
	}

	/**
	 * @inheritDoc
	 */
	public function render( Parser $parser, PPFrame $frame, array $params ): string {
		$params = new ParameterParser( $frame, $params, [
			ListFunctions::PARAM_OPTIONS['list'],
			ListFunctions::PARAM_OPTIONS['insep'],
			ListFunctions::PARAM_OPTIONS['list'],
			ListFunctions::PARAM_OPTIONS['insep'],
			ListFunctions::PARAM_OPTIONS['outsep']
		] );

		$inList1 = $params->get( 0 );
		$inList2 = $params->get( 2 );
		if ( $inList1 === '' && $inList2 === '' ) {
			return '';
		}

		if ( $inList1 === '' ) {
			$values1 = [];
		} else {
			$inSep1 = $params->get( 1 );
			$inSep1 = $parser->getStripState()->unstripNoWiki( $inSep1 );
			$values1 = ListFunctions::explodeList( $inSep1, $inList1 );
		}

		if ( $inList2 === '' ) {
			$values2 = [];
		} else {
			$inSep2 = $params->get( 3 );
			$inSep2 = $parser->getStripState()->unstripNoWiki( $inSep2 );
			$values2 = ListFunctions::explodeList( $inSep2, $inList2 );
		}

		$outSep = $params->get( 4 );

		$values = array_merge( $values1, $values2 );
		return ParserPower::evaluateUnescaped( $parser, $frame, ListFunctions::implodeList( $values, $outSep ) );
	}
}
