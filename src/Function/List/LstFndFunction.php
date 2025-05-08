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
 * Parser function for searching a list value (#lstfnd).
 */
final class LstFndFunction implements ParserFunction {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'lstfnd';
	}

	/**
	 * @inheritDoc
	 */
	public function render( Parser $parser, PPFrame $frame, array $params ): string {
		$params = new ParameterParser( $frame, $params, [
			[ 'unescape' => true ],
			ListFunctions::PARAM_OPTIONS['list'],
			ListFunctions::PARAM_OPTIONS['insep'],
			[]
		] );

		$list = $params->get( 1 );

		if ( $list === '' ) {
			return '';
		}

		$item = $params->get( 0 );
		$sep = $params->get( 2 );
		$sep = $parser->getStripState()->unstripNoWiki( $sep );
		$csOption = $params->get( 3 );
		$csOption = ListFunctions::decodeCSOption( $csOption );

		$values = ListFunctions::explodeList( $sep, $list );
		if ( $csOption ) {
			foreach ( $values as $value ) {
				if ( $value === $item ) {
					return ParserPower::evaluateUnescaped( $parser, $frame, $value );
				}
			}
		} else {
			foreach ( $values as $value ) {
				if ( strtolower( $value ) === strtolower( $item ) ) {
					return ParserPower::evaluateUnescaped( $parser, $frame, $value );
				}
			}
		}
		return '';
	}
}
