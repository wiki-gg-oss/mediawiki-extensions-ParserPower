<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function\List;

use MediaWiki\Extension\ParserPower\ListUtils;
use MediaWiki\Extension\ParserPower\ParameterParser;
use MediaWiki\Extension\ParserPower\ParserPower;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

/**
 * Parser function for removing non-unique list values from an identity pattern (#lstuniq).
 */
final class LstUniqFunction extends ListUniqueFunction {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'lstuniq';
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
			ListUtils::PARAM_OPTIONS['insep'],
			ListUtils::PARAM_OPTIONS['outsep'],
			[]
		];
	}

	/**
	 * @inheritDoc
	 */
	public function execute( Parser $parser, PPFrame $frame, ParameterParser $params ): string {
		$inList = $params->get( 0 );

		if ( $inList === '' ) {
			return '';
		}

		$inSep = $params->get( 1 );
		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );
		$outSep = $params->get( 2 );
		$csOption = ListUtils::decodeCSOption( $params->get( 3 ) );

		$values = ListUtils::explode( $inSep, $inList );
		$values = $this->reduceToUniqueValues( $values, $csOption );
		return ParserPower::evaluateUnescaped( $parser, $frame, ListUtils::implode( $values, $outSep ) );
	}
}
