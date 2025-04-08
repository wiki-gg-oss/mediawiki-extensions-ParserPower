<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function\List;

use MediaWiki\Extension\ParserPower\ListSorter;
use MediaWiki\Extension\ParserPower\ListUtils;
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
			ListUtils::PARAM_OPTIONS['list'],
			ListUtils::PARAM_OPTIONS['insep'],
			ListUtils::PARAM_OPTIONS['outsep'],
			ListUtils::PARAM_OPTIONS['sortoptions']
		] );

		$inList = $params->get( 0 );

		if ( $inList === '' ) {
			return '';
		}

		$inSep = $params->get( 1 );
		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );
		$values = ListUtils::explode( $inSep, $inList );

		if ( count( $values ) === 0 ) {
			return '';
		}

		$sortOptions = ListUtils::decodeSortOptions( $params->get( 3 ) );
		$sorter = new ListSorter( $sortOptions );
		$values = $sorter->sort( $values );

		$outSep = count( $values ) > 1 ? $params->get( 2 ) : '';
		$outList = ListUtils::implode( $values, $outSep );

		return ParserPower::evaluateUnescaped( $parser, $frame, $outList );
	}
}
