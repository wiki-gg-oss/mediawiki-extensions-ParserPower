<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function\List;

use MediaWiki\Extension\ParserPower\ListUtils;
use MediaWiki\Extension\ParserPower\ParameterParser;
use MediaWiki\Extension\ParserPower\ParserPower;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;
use MediaWiki\Extension\ParserPower\Function\ParserFunctionBase;

/**
 * Parser function for joining two lists (#lstjoin).
 */
final class LstJoinFunction extends ParserFunctionBase {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'lstjoin';
	}

	/**
	 * @inheritDoc
	 */
	public function getParamSpec(): array {
		return [
			...ListUtils::PARAM_OPTIONS,
			0 => 'list1',
			1 => 'insep1',
			2 => 'list2',
			3 => 'insep2',
			4 => 'outsep'
		];
	}

	/**
	 * @inheritDoc
	 */
	public function execute( Parser $parser, PPFrame $frame, ParameterParser $params ): string {
		$inList1 = $params->get( 'list1' );
		$inSep1 = $inList1 !== '' ? $params->get( 'insep1' ) : '';
		$inSep1 = $parser->getStripState()->unstripNoWiki( $inSep1 );
		$values1 = ListUtils::explode( $inSep1, $inList1 );

		$inList2 = $params->get( 'list2' );
		$inSep2 = $inList2 !== '' ? $params->get( 'insep2' ) : '';
		$inSep2 = $parser->getStripState()->unstripNoWiki( $inSep2 );
		$values2 = ListUtils::explode( $inSep2, $inList2 );

		$values = array_merge( $values1, $values2 );

		$outSep = count( $values ) > 1 ? $params->get( 'outsep' ) : '';
		$outList = ListUtils::implode( $values, $outSep );

		return ParserPower::evaluateUnescaped( $parser, $frame, $outList );
	}
}
