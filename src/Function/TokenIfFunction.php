<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function;

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
		$inValue = ParserPower::expand( $frame, $params[0] ?? '' );

		if ( $inValue === '' ) {
			$default = ParserPower::expand( $frame, $params[3] ?? '', ParserPower::UNESCAPE );
			return ParserPower::evaluateUnescaped( $parser, $frame, $default );
		}

		$token = ParserPower::expand( $frame, $params[1] ?? 'x', ParserPower::UNESCAPE );
		$pattern = ParserPower::expand( $frame, $params[2] ?? 'x', ParserPower::NO_VARS );

		$outValue = ParserPower::applyPattern( $inValue, $token, $pattern );
		$outValue = $parser->preprocessToDom( $outValue, $frame->isTemplate() ? Parser::PTD_FOR_INCLUSION : 0 );
		$outValue = ParserPower::expand( $frame, $outValue, ParserPower::UNESCAPE );

		return ParserPower::evaluateUnescaped( $parser, $frame, $outValue );
	}
}
