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
			...ListUtils::PARAM_OPTIONS,
			0 => 'list',
			1 => 'insep',
			2 => 'csoption'
		];
	}

	/**
	 * @inheritDoc
	 */
	public function execute( Parser $parser, PPFrame $frame, ParameterParser $params ): string {
		$inList = $params->get( 'list' );
		$sep = $inList !== '' ? $params->get( 'insep' ) : '';
		$sep = $parser->getStripState()->unstripNoWiki( $sep );
		$values = ListUtils::explode( $sep, $inList );

		if ( count( $values ) === 0 ) {
			return '0';
		}

		$csOption = ListUtils::decodeCSOption( $params->get( 'csoption' ) );
		$values = $this->reduceToUniqueValues( $values, $csOption );

		return (string)count( $values );
	}
}
