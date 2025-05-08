<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function\List;

use MediaWiki\Extension\ParserPower\ListFunctions;
use MediaWiki\Extension\ParserPower\ListSorter;
use MediaWiki\Extension\ParserPower\ParameterParser;
use MediaWiki\Extension\ParserPower\ParserPower;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

/**
 * Parser function for sorting list values from an identity pattern (#lstsrt).
 */
final class LstSrtFunction extends ListSortFunction {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'lstsrt';
	}

	/**
	 * @inheritDoc
	 */
	public function render( Parser $parser, PPFrame $frame, array $params ): string {
		$params = new ParameterParser( $frame, $params, [
			ListFunctions::PARAM_OPTIONS['list'],
			ListFunctions::PARAM_OPTIONS['insep'],
			ListFunctions::PARAM_OPTIONS['outsep'],
			ListFunctions::PARAM_OPTIONS['sortoptions']
		] );

		$inList = $params->get( 0 );

		if ( $inList === '' ) {
			return '';
		}

		$inSep = $params->get( 1 );
		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );
		$outSep = $params->get( 2 );

		$sortOptions = ListFunctions::decodeSortOptions( $params->get( 3 ) );
		$sorter = new ListSorter( $sortOptions );

		$values = ListFunctions::explodeList( $inSep, $inList );
		$values = $sorter->sort( $values );
		return ParserPower::evaluateUnescaped( $parser, $frame, ListFunctions::implodeList( $values, $outSep ) );
	}
}
