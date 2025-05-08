<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function\List;

use MediaWiki\Extension\ParserPower\ListFunctions;
use MediaWiki\Extension\ParserPower\ParameterParser;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

/**
 * Parser function for counting non-unique list values (#lstcntuniq).
 */
final class LstCntUniqFunction extends ListUniqueFunction {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'lstcntuniq';
	}

	/**
	 * @inheritDoc
	 */
	public function render( Parser $parser, PPFrame $frame, array $params ): string {
		$params = new ParameterParser( $frame, $params, [
			ListFunctions::PARAM_OPTIONS['list'],
			ListFunctions::PARAM_OPTIONS['insep'],
			[]
		] );

		$inList = $params->get( 0 );
		if ( $inList === '' ) {
			return '0';
		}

		$sep = $params->get( 1 );
		$sep = $parser->getStripState()->unstripNoWiki( $sep );
		$csOption = ListFunctions::decodeCSOption( $params->get( 2 ) );

		$values = ListFunctions::explodeList( $sep, $inList );
		$values = $this->reduceToUniqueValues( $values, $csOption );
		return (string)count( $values );
	}
}
