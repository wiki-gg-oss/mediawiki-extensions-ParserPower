<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function\List;

use MediaWiki\Extension\ParserPower\ListFunctions;
use MediaWiki\Extension\ParserPower\ListSorter;
use MediaWiki\Extension\ParserPower\Operation\TemplateOperation;
use MediaWiki\Extension\ParserPower\ParameterParser;
use MediaWiki\Extension\ParserPower\ParserPower;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

/**
 * Parser function for mapping list values from a template (#lstmaptemp).
 */
final class LstMapTempFunction extends ListMapFunction {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'lstmaptemp';
	}

	/**
	 * @inheritDoc
	 */
	public function render( Parser $parser, PPFrame $frame, array $params ): string {
		$params = new ParameterParser( $frame, $params, [
			ListFunctions::PARAM_OPTIONS['list'],
			ListFunctions::PARAM_OPTIONS['template'],
			ListFunctions::PARAM_OPTIONS['insep'],
			ListFunctions::PARAM_OPTIONS['outsep'],
			[],
			ListFunctions::PARAM_OPTIONS['sortoptions']
		] );

		$inList = $params->get( 0 );

		if ( $inList === '' ) {
			return '';
		}

		$template = $params->get( 1 );
		$inSep = $params->get( 2 );
		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );
		$outSep = $params->get( 3 );
		$sortMode = ListFunctions::decodeSortMode( $params->get( 4 ) );
		$sortOptions = ListFunctions::decodeSortOptions( $params->get( 5 ) );

		$sorter = new ListSorter( $sortOptions );

		$inValues = ListFunctions::explodeList( $inSep, $inList );

		if ( $sortMode & ListFunctions::SORTMODE_PRE ) {
			$inValues = $sorter->sort( $inValues );
		}

		$operation = new TemplateOperation( $parser, $frame, $template );
		$outValues = $this->mapList( $operation, true, $inValues, '' );

		if ( $sortMode & ( ListFunctions::SORTMODE_POST | ListFunctions::SORTMODE_COMPAT ) ) {
			$outValues = $sorter->sort( $outValues );
		}

		if ( count( $outValues ) === 0 ) {
			return '';
		}

		return ParserPower::evaluateUnescaped( $parser, $frame, ListFunctions::implodeList( $outValues, $outSep ) );
	}
}
