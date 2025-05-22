<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function\List;

use MediaWiki\Extension\ParserPower\ListSorter;
use MediaWiki\Extension\ParserPower\ListUtils;
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
	public function allowsNamedParams(): bool {
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function getParamSpec(): array {
		$paramSpec = [
			...ListUtils::PARAM_OPTIONS,
			0 => 'list',
			1 => 'insep',
			2 => 'token',
			3 => 'pattern',
			4 => 'outsep',
			5 => 'sortmode',
			6 => 'sortoptions'
		];

		$legacyExpansionFlags = $this->useLegacyExpansion ? [ 'novars' => true ] : [];
		$paramSpec['token'] = [ ...$paramSpec['token'], 'default' => 'x', ...$legacyExpansionFlags ];
		$paramSpec['pattern'] = [ ...$paramSpec['pattern'], 'default' => 'x', ...$legacyExpansionFlags ];

		return $paramSpec;
	}

	/**
	 * @inheritDoc
	 */
	public function execute( Parser $parser, PPFrame $frame, ParameterParser $params ): string {
		$inList = $params->get( 'list' );
		$inSep = $inList !== '' ? $params->get( 'insep' ) : '';
		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );
		$inValues = ListUtils::explode( $inSep, $inList );

		if ( count( $inValues ) === 0 ) {
			return '';
		}

		$token = $params->get( 'token' );
		$pattern = $params->get( 'pattern' );

		$sortMode = ListUtils::decodeSortMode( $params->get( 'sortmode' ) );
		$sortOptions = $sortMode > 0 ? ListUtils::decodeSortOptions( $params->get( 'sortoptions' ) ) : 0;
		$sorter = new ListSorter( $sortOptions );

		if ( $sortMode & ListUtils::SORTMODE_PRE ) {
			$inValues = $sorter->sort( $inValues );
		}

		$operation = new PatternOperation( $parser, $frame, $pattern, [ $token ] );
		$outValues = $this->mapList( $operation, false, $inValues, '' );

		if ( $sortMode & ( ListUtils::SORTMODE_COMPAT | ListUtils::SORTMODE_POST ) ) {
			$outValues = $sorter->sort( $outValues );
		}

		$outSep = count( $outValues ) > 1 ? $params->get( 'outsep' ) : '';
		$outList = ListUtils::implode( $outValues, $outSep );

		return ParserPower::evaluateUnescaped( $parser, $frame, $outList );
	}
}
