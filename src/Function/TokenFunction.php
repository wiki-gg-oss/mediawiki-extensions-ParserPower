<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function;

use MediaWiki\Extension\ParserPower\ParserPower;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

/**
 * Parser function for replacing a token in a pattern (#token).
 */
final class TokenFunction implements ParserFunction {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'token';
	}

	/**
	 * @inheritDoc
	 */
	public function render( Parser $parser, PPFrame $frame, array $params ): string {
		$inValue = ParserPower::expand( $frame, $params[0] ?? '' );

		$token = ParserPower::expand( $frame, $params[1] ?? 'x', ParserPower::UNESCAPE );
		$pattern = ParserPower::expand( $frame, $params[2] ?? 'x', ParserPower::NO_VARS );

		$outValue = ParserPower::applyPattern( $inValue, $token, $pattern );
		$outValue = $parser->preprocessToDom( $outValue, $frame->isTemplate() ? Parser::PTD_FOR_INCLUSION : 0 );
		$outValue = ParserPower::expand( $frame, $outValue, ParserPower::UNESCAPE );

		return ParserPower::evaluateUnescaped( $parser, $frame, $outValue );
	}
}
