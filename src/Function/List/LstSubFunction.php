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
 * Parser function for subdividing a list (#lstsub).
 */
final class LstSubFunction implements ParserFunction {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'lstsub';
	}

	/**
	 * @inheritDoc
	 */
	public function render( Parser $parser, PPFrame $frame, array $params ): string {
		$params = new ParameterParser( $frame, $params, [
			ListUtils::PARAM_OPTIONS['list'],
			ListUtils::PARAM_OPTIONS['insep'],
			ListUtils::PARAM_OPTIONS['outsep'],
			[ 'unescape' => true ],
			[ 'unescape' => true ]
		] );

		$inList = $params->get( 0 );

		if ( $inList === '' ) {
			return '';
		}

		$inSep = $params->get( 1 );
		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );
		$outSep = $params->get( 2 );
		$offset = $params->get( 3 );
		$offset = is_numeric( $offset ) ? intval( $offset ) : 0;
		$length = $params->get( 4 );
		$length = is_numeric( $length ) ? intval( $length ) : null;

		$values = ListUtils::slice( ListUtils::explode( $inSep, $inList ), $offset, $length );

		if ( count( $values ) > 0 ) {
			return ParserPower::evaluateUnescaped( $parser, $frame, ListUtils::implode( $values, $outSep ) );
		} else {
			return '';
		}
	}
}
