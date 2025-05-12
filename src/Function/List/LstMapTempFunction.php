<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function\List;

use MediaWiki\Extension\ParserPower\ListSorter;
use MediaWiki\Extension\ParserPower\ListUtils;
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
			ListUtils::PARAM_OPTIONS['list'],
			ListUtils::PARAM_OPTIONS['template'],
			ListUtils::PARAM_OPTIONS['insep'],
			ListUtils::PARAM_OPTIONS['outsep'],
			[],
			ListUtils::PARAM_OPTIONS['sortoptions']
		] );

		$inList = $params->get( 0 );

		if ( $inList === '' ) {
			return '';
		}

		$template = $params->get( 1 );
		$inSep = $params->get( 2 );
		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );
		$outSep = $params->get( 3 );
		$sortMode = ListUtils::decodeSortMode( $params->get( 4 ) );
		$sortOptions = ListUtils::decodeSortOptions( $params->get( 5 ) );

		$sorter = new ListSorter( $sortOptions );

		$inValues = ListUtils::explode( $inSep, $inList );

		if ( $sortMode & ListUtils::SORTMODE_PRE ) {
			$inValues = $sorter->sort( $inValues );
		}

		$operation = new TemplateOperation( $parser, $frame, $template );
		$outValues = $this->mapList( $operation, true, $inValues, '' );

		if ( $sortMode & ( ListUtils::SORTMODE_POST | ListUtils::SORTMODE_COMPAT ) ) {
			$outValues = $sorter->sort( $outValues );
		}

		if ( count( $outValues ) === 0 ) {
			return '';
		}

		return ParserPower::evaluateUnescaped( $parser, $frame, ListUtils::implode( $outValues, $outSep ) );
	}
}
