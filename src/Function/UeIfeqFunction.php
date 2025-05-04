<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function;

use MediaWiki\Extension\ParserPower\ParserPower;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

/**
 * Parser function for #ifeq with unescaped parameters (#ueifeq).
 */
final class UeIfeqFunction implements ParserFunction {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'ueifeq';
	}

	/**
	 * @inheritDoc
	 */
	public function render( Parser $parser, PPFrame $frame, array $params ): string {
		$leftValue = ParserPower::expand( $frame, $params[0] ?? '', ParserPower::UNESCAPE );
		$rightValue = ParserPower::expand( $frame, $params[1] ?? '', ParserPower::UNESCAPE );

		if ( $leftValue === $rightValue ) {
			$value = ParserPower::expand( $frame, $params[2] ?? '', ParserPower::UNESCAPE );
		} else {
			$value = ParserPower::expand( $frame, $params[3] ?? '', ParserPower::UNESCAPE );
		}

		return ParserPower::evaluateUnescaped( $parser, $frame, $value );
	}
}
