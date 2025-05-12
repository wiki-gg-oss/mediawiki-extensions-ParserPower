<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function;

use MediaWiki\Extension\ParserPower\ParameterParser;
use MediaWiki\Extension\ParserPower\ParserPower;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

/**
 * Parser function for nesting #token in #if (#tokenif).
 */
final class TokenIfFunction implements ParserFunction {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'tokenif';
	}

	/**
	 * @inheritDoc
	 */
	public function render( Parser $parser, PPFrame $frame, array $params ): string {
		$params = new ParameterParser( $frame, $params, [
			0 => [],
			1 => [ 'default' => 'x', 'unescape' => true ],
			2 => [ 'default' => 'x', 'novars' => true ],
			3 => [ 'unescape' => true ]
		] );

		$inValue = $params->get( 0 );
		if ( $inValue === '' ) {
			return ParserPower::evaluateUnescaped( $parser, $frame, $params->get( 3 ) );
		}

		$outValue = ParserPower::applyPattern( $inValue, $params->get( 1 ), $params->get( 2 ) );
		$outValue = $parser->preprocessToDom( $outValue, $frame->isTemplate() ? Parser::PTD_FOR_INCLUSION : 0 );
		$outValue = ParserPower::expand( $frame, $outValue, ParserPower::UNESCAPE );

		return ParserPower::evaluateUnescaped( $parser, $frame, $outValue );
	}
}
