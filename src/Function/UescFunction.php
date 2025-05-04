<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function;

use MediaWiki\Extension\ParserPower\ParserPower;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

/**
 * Parser function for unescaping a value (#uesc).
 */
final class UescFunction implements ParserFunction {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'uesc';
	}

	/**
	 * @inheritDoc
	 */
	public function render( Parser $parser, PPFrame $frame, array $params ): string {
		$text = ParserPower::expand( $frame, $params[0] ?? '', ParserPower::UNESCAPE );

		return ParserPower::evaluateUnescaped( $parser, $frame, $text );
	}
}
