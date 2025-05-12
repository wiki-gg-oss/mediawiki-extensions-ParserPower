<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function\List;

use MediaWiki\Extension\ParserPower\ListUtils;
use MediaWiki\Extension\ParserPower\Operation\ListInclusionOperation;
use MediaWiki\Extension\ParserPower\ParameterParser;
use MediaWiki\Extension\ParserPower\ParserPower;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

/**
 * Parser function for filtering list values from an exclusion value (#lstrm).
 */
final class LstRmFunction extends ListFilterFunction {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'lstrm';
	}

	/**
	 * @inheritDoc
	 */
	public function render( Parser $parser, PPFrame $frame, array $params ): string {
		$params = new ParameterParser( $frame, $params, [
			[ 'unescape' => true ],
			ListUtils::PARAM_OPTIONS['list'],
			ListUtils::PARAM_OPTIONS['insep'],
			ListUtils::PARAM_OPTIONS['outsep'],
			[]
		] );

		$inList = $params->get( 1 );

		if ( $inList === '' ) {
			return '';
		}

		$value = $params->get( 0 );
		$inSep = $params->get( 2 );
		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );
		$outSep = $params->get( 3 );
		$csOption = ListUtils::decodeCSOption( $params->get( 4 ) );

		$inValues = ListUtils::explode( $inSep, $inList );

		$operation = new ListInclusionOperation( [ $value ], 'remove', '', $csOption );
		$outValues = $this->filterList( $operation, $inValues );

		if ( count( $outValues ) > 0 ) {
			return ParserPower::evaluateUnescaped( $parser, $frame, ListUtils::implode( $outValues, $outSep ) );
		} else {
			return '';
		}
	}
}
