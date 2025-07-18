<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function\List;

use MediaWiki\Extension\ParserPower\ListUtils;
use MediaWiki\Extension\ParserPower\Parameters;
use MediaWiki\Extension\ParserPower\ParserPower;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

/**
 * Parser function for retrieving a value from a list (#lstelem).
 */
final class LstElemFunction extends ListFunction {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'lstelem';
	}

	/**
	 * @inheritDoc
	 */
	public function getParamSpec(): array {
		$paramSpec = [
			...parent::getParamSpec(),
			0 => 'list',
			1 => 'insep',
			2 => 'index'
		];

		$paramSpec['index']['default'] = 1;

		return $paramSpec;
	}

	/**
	 * @inheritDoc
	 */
	public function execute( Parser $parser, PPFrame $frame, Parameters $params ): string {
		$inList = $params->get( 'list' );
		$inSep = $inList !== '' ? $params->get( 'insep' ) : '';
		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );
		$inValues = ListUtils::explode( $inSep, $inList );

		if ( empty( $inValues ) ) {
			return '';
		}

		$index = $params->get( 'index' );
		$value = ListUtils::get( $inValues, $index );

		return ParserPower::evaluateUnescaped( $parser, $frame, $value );
	}
}
