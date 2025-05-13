<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function\List;

use MediaWiki\Extension\ParserPower\ListUtils;
use MediaWiki\Extension\ParserPower\ParameterParser;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

/**
 * Parser function for counting non-unique list values (#lstcntuniq).
 */
final class LstCntUniqFunction extends ListUniqueFunction {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'lstcntuniq';
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
			[]
		];
	}

	/**
	 * @inheritDoc
	 */
	public function execute( Parser $parser, PPFrame $frame, ParameterParser $params ): string {
		$inList = $params->get( 0 );
		if ( $inList === '' ) {
			return '0';
		}

		$sep = $params->get( 1 );
		$sep = $parser->getStripState()->unstripNoWiki( $sep );
		$csOption = ListUtils::decodeCSOption( $params->get( 2 ) );

		$values = ListUtils::explode( $sep, $inList );
		$values = $this->reduceToUniqueValues( $values, $csOption );
		return (string)count( $values );
	}
}
