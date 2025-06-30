<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function\List;

use MediaWiki\Extension\ParserPower\ListUtils;
use MediaWiki\Extension\ParserPower\Parameters;
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
			...parent::getParamSpec(),
			0 => 'list',
			1 => 'insep',
			2 => 'outsep',
			3 => 'csoption'
		];
	}

	/**
	 * @inheritDoc
	 */
	public function execute( Parser $parser, PPFrame $frame, Parameters $params ): string {
		$inList = $params->get( 'list' );
		$inSep = $inList !== '' ? $params->get( 'insep' ) : '';
		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );
		$values = ListUtils::explode( $inSep, $inList );

		if ( count( $values ) === 0 ) {
			return '';
		}

		$csOption = $params->get( 'csoption' );
		$values = $this->reduceToUniqueValues( $values, $csOption );

		$outSep = count( $values ) > 1 ? $params->get( 'outsep' ) : '';
		$outList = ListUtils::implode( $values, $outSep );

		return ParserPower::evaluateUnescaped( $parser, $frame, $outList );
	}
}
