<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function;

use MediaWiki\Extension\ParserPower\ParserPower;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

/**
 * Parser function for #if with unescaped parameters (#ueif).
 */
final class UeIfFunction implements ParserFunction {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'ueif';
	}

	/**
	 * @inheritDoc
	 */
	public function render( Parser $parser, PPFrame $frame, array $params ): string {
		$condition = ParserPower::expand( $frame, $params[0] ?? '' );

		if ( $condition !== '' ) {
			$value = ParserPower::expand( $frame, $params[1] ?? '', ParserPower::UNESCAPE );
		} else {
			$value = ParserPower::expand( $frame, $params[2] ?? '', ParserPower::UNESCAPE );
		}

		return ParserPower::evaluateUnescaped( $parser, $frame, $value );
	}
}
