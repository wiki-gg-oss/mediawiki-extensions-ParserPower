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
 * Parser function for replacing the value separator of a list (#lstsep).
 */
final class LstSepFunction implements ParserFunction {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'lstsep';
	}

	/**
	 * @inheritDoc
	 */
	public function render( Parser $parser, PPFrame $frame, array $params ): string {
		$params = new ParameterParser( $frame, $params, [
			ListUtils::PARAM_OPTIONS['list'],
			ListUtils::PARAM_OPTIONS['insep'],
			ListUtils::PARAM_OPTIONS['outsep']
		] );

		$inList = $params->get( 0 );
		if ( $inList === '' ) {
			return '';
		}

		$inSep = $params->get( 1 );
		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );
		$outSep = $params->get( 2 );

		$values = ListUtils::explode( $inSep, $inList );
		return ParserPower::evaluateUnescaped( $parser, $frame, ListUtils::implode( $values, $outSep ) );
	}
}
