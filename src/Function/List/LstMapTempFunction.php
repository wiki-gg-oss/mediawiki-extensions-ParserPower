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
	public function allowsNamedParams(): bool {
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function getParamSpec(): array {
		return [
			ListUtils::PARAM_OPTIONS['list'],
			ListUtils::PARAM_OPTIONS['template'],
			ListUtils::PARAM_OPTIONS['insep'],
			ListUtils::PARAM_OPTIONS['outsep'],
			[],
			ListUtils::PARAM_OPTIONS['sortoptions']
		];
	}

	/**
	 * @inheritDoc
	 */
	public function execute( Parser $parser, PPFrame $frame, ParameterParser $params ): string {
		$inList = $params->get( 0 );
		$inSep = $inList !== '' ? $params->get( 2 ) : '';
		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );
		$inValues = ListUtils::explode( $inSep, $inList );

		if ( count( $inValues ) === 0 ) {
			return '';
		}

		$template = $params->get( 1 );

		$sortMode = ListUtils::decodeSortMode( $params->get( 4 ) );
		$sortOptions = $sortMode > 0 ? ListUtils::decodeSortOptions( $params->get( 5 ) ) : 0;
		$sorter = new ListSorter( $sortOptions );

		if ( $sortMode & ListUtils::SORTMODE_PRE ) {
			$inValues = $sorter->sort( $inValues );
		}

		$operation = new TemplateOperation( $parser, $frame, $template );
		$outValues = $this->mapList( $operation, true, $inValues, '' );

		if ( $sortMode & ( ListUtils::SORTMODE_POST | ListUtils::SORTMODE_COMPAT ) ) {
			$outValues = $sorter->sort( $outValues );
		}

		$outSep = count( $outValues ) > 1 ? $params->get( 3 ) : '';
		$outList = ListUtils::implode( $outValues, $outSep );

		return ParserPower::evaluateUnescaped( $parser, $frame, $outList );
	}
}
