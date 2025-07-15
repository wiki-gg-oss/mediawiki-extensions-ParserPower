<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function\List;

use MediaWiki\Extension\ParserPower\ListUtils;
use MediaWiki\Extension\ParserPower\Operation\ListInclusionOperation;
use MediaWiki\Extension\ParserPower\Parameters;
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
	public function getParserFlags(): int {
		return 0;
	}

	/**
	 * @inheritDoc
	 */
	public function getParamSpec(): array {
		return [
			...parent::getParamSpec(),
			0 => 'value',
			1 => 'list',
			2 => 'insep',
			3 => 'outsep',
			4 => 'csoption'
		];
	}

	/**
	 * @inheritDoc
	 */
	public function execute( Parser $parser, PPFrame $frame, Parameters $params ): string {
		$inList = $params->get( 'list' );
		$inSep = $inList !== '' ? $params->get( 'insep' ) : '';
		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );
		$inValues = ListUtils::explode( $inSep, $inList );

		if ( count( $inValues ) === 0 ) {
			return '';
		}

		$value = $params->get( 'value' );
		$csOption = $params->get( 'csoption' );
		$operation = new ListInclusionOperation( [ $value ], 'remove', '', $csOption );
		$outValues = $this->filterList( $operation, $inValues );

		return ParserPower::evaluateUnescaped( $parser, $frame, $this->implodeOutList( $params, $outValues ) );
	}
}
