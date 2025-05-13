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
 * Parser function for replacing the value separator of a list (#lstsep).
 */
final class LstSepFunction extends ParserFunctionBase {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'lstsep';
	}

	/**
	 * @inheritDoc
	 */
	public function getParamSpec(): array {
		return [
			ListUtils::PARAM_OPTIONS['list'],
			ListUtils::PARAM_OPTIONS['insep'],
			ListUtils::PARAM_OPTIONS['outsep']
		];
	}

	/**
	 * @inheritDoc
	 */
	public function execute( Parser $parser, PPFrame $frame, ParameterParser $params ): string {
		$inList = $params->get( 0 );
		$inSep = $inList !== '' ? $params->get( 1 ) : '';
		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );
		$values = ListUtils::explode( $inSep, $inList );

		$outSep = count( $values ) > 1 ? $params->get( 2 ) : '';
		$outList = ListUtils::implode( $values, $outSep );

		return ParserPower::evaluateUnescaped( $parser, $frame, $outList );
	}
}
