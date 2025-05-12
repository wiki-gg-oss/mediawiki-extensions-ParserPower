<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function\List;

use MediaWiki\Extension\ParserPower\ListFunctions;
use MediaWiki\Extension\ParserPower\ListSorter;
use MediaWiki\Extension\ParserPower\Operation\PatternOperation;
use MediaWiki\Extension\ParserPower\ParameterParser;
use MediaWiki\Extension\ParserPower\ParserPower;
use MediaWiki\Extension\ParserPower\ParserPowerConfig;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

/**
 * Parser function for mapping list values from a pattern (#lstmap).
 */
final class LstMapFunction extends ListMapFunction {

	/**
	 * @var bool Whether patterns and tokens should be expanded after token replacements.
	 */
	private bool $useLegacyExpansion;

	/**
	 * @param ParserPowerConfig $config
	 */
	public function __construct( ParserPowerConfig $config ) {
		$this->useLegacyExpansion = $config->get( 'LstmapExpansionCompat' );
	}

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'lstmap';
	}

	/**
	 * @inheritDoc
	 */
	public function render( Parser $parser, PPFrame $frame, array $params ): string {
		$legacyExpansionFlags = $this->useLegacyExpansion ? [ 'novars' => true ] : [];
		$params = new ParameterParser( $frame, $params, [
			ListFunctions::PARAM_OPTIONS['list'],
			ListFunctions::PARAM_OPTIONS['insep'],
			array_merge( ListFunctions::PARAM_OPTIONS['token'], [ 'default' => 'x' ], $legacyExpansionFlags ),
			array_merge( ListFunctions::PARAM_OPTIONS['pattern'], [ 'default' => 'x' ], $legacyExpansionFlags ),
			ListFunctions::PARAM_OPTIONS['outsep'],
			[],
			ListFunctions::PARAM_OPTIONS['sortoptions']
		] );

		$inList = $params->get( 0 );

		if ( $inList === '' ) {
			return '';
		}

		$inSep = $params->get( 1 );
		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );
		$token = $params->get( 2 );
		$pattern = $params->get( 3 );
		$outSep = $params->get( 4 );
		$sortMode = ListFunctions::decodeSortMode( $params->get( 5 ) );
		$sortOptions = ListFunctions::decodeSortOptions( $params->get( 6 ) );

		$sorter = new ListSorter( $sortOptions );

		$inValues = ListFunctions::explodeList( $inSep, $inList );

		if ( $sortMode & ListFunctions::SORTMODE_PRE ) {
			$inValues = $sorter->sort( $inValues );
		}

		$operation = new PatternOperation( $parser, $frame, $pattern, [ $token ] );
		$outValues = $this->mapList( $operation, false, $inValues, '' );

		if ( $sortMode & ( ListFunctions::SORTMODE_COMPAT | ListFunctions::SORTMODE_POST ) ) {
			$outValues = $sorter->sort( $outValues );
		}

		if ( count( $outValues ) === 0 ) {
			return '';
		}

		return ParserPower::evaluateUnescaped( $parser, $frame, ListFunctions::implodeList( $outValues, $outSep ) );
	}
}
