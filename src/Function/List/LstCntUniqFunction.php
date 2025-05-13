<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function\List;

use MediaWiki\Extension\ParserPower\ListUtils;
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
			ListUtils::PARAM_OPTIONS['list'],
			ListUtils::PARAM_OPTIONS['insep'],
			[]
		] );

		$inList = $params->get( 0 );
		$sep = $inList !== '' ? $params->get( 1 ) : '';
		$sep = $parser->getStripState()->unstripNoWiki( $sep );
		$values = ListUtils::explode( $sep, $inList );

		if ( count( $values ) === 0 ) {
			return '0';
		}

		$csOption = ListUtils::decodeCSOption( $params->get( 2 ) );
		$values = $this->reduceToUniqueValues( $values, $csOption );

		return (string)count( $values );
	}
}
