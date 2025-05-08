<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function\List;

use MediaWiki\Extension\ParserPower\ListFunctions;
use MediaWiki\Extension\ParserPower\Operation\ListInclusionOperation;
use MediaWiki\Extension\ParserPower\ParameterParser;
use MediaWiki\Extension\ParserPower\ParserPower;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

/**
 * Parser function for filtering list values from an inclusion list (#lstfltr).
 */
final class LstFltrFunction extends ListFilterFunction {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'lstfltr';
	}

	/**
	 * @inheritDoc
	 */
	public function render( Parser $parser, PPFrame $frame, array $params ): string {
		$params = new ParameterParser( $frame, $params, [
			ListFunctions::PARAM_OPTIONS['keep'],
			ListFunctions::PARAM_OPTIONS['keepsep'],
			ListFunctions::PARAM_OPTIONS['list'],
			ListFunctions::PARAM_OPTIONS['insep'],
			ListFunctions::PARAM_OPTIONS['outsep'],
			[]
		] );

		$inList = $params->get( 2 );

		if ( $inList === '' ) {
			return '';
		}

		$values = $params->get( 0 );
		$valueSep = $params->get( 1 );
		$inSep = $params->get( 3 );
		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );
		$outSep = $params->get( 4 );
		$csOption = ListFunctions::decodeCSOption( $params->get( 5 ) );

		$inValues = ListFunctions::explodeList( $inSep, $inList );

		if ( $valueSep !== '' ) {
			$values = ListFunctions::explodeList( $valueSep, $values );
		} else {
			$values = [ ParserPower::unescape( $values ) ];
		}

		$operation = new ListInclusionOperation( $values, '', 'remove', $csOption );
		$outValues = $this->filterList( $operation, $inValues );

		if ( count( $outValues ) > 0 ) {
			return ParserPower::evaluateUnescaped( $parser, $frame, ListFunctions::implodeList( $outValues, $outSep ) );
		} else {
			return '';
		}
	}
}
