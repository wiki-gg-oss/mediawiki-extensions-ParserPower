<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function\List;

use MediaWiki\Extension\ParserPower\ListUtils;
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
			ListUtils::PARAM_OPTIONS['list'],
			ListUtils::PARAM_OPTIONS['insep'],
			ListUtils::PARAM_OPTIONS['list'],
			ListUtils::PARAM_OPTIONS['insep'],
			ListUtils::PARAM_OPTIONS['outsep']
		] );

		$inList1 = $params->get( 0 );
		$inSep1 = $inList1 !== '' ? $params->get( 1 ) : '';
		$inSep1 = $parser->getStripState()->unstripNoWiki( $inSep1 );
		$values1 = ListUtils::explode( $inSep1, $inList1 );

		$inList2 = $params->get( 2 );
		$inSep2 = $inList2 !== '' ? $params->get( 3 ) : '';
		$inSep2 = $parser->getStripState()->unstripNoWiki( $inSep2 );
		$values2 = ListUtils::explode( $inSep2, $inList2 );

		$values = array_merge( $values1, $values2 );

		$outSep = count( $values ) > 1 ? $params->get( 4 ) : '';
		$outList = ListUtils::implode( $values, $outSep );

		return ParserPower::evaluateUnescaped( $parser, $frame, $outList );
	}
}
