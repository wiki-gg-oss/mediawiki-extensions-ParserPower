<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function;

use MediaWiki\Extension\ParserPower\ParameterParser;
use MediaWiki\Extension\ParserPower\ParserPower;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

/**
 * Parser function for unescaping then trimming a value (#trimuesc),
 * so any leading or trailing whitespace is trimmed no matter how it got there.
 */
final class TrimUescFunction implements ParserFunction {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'trimuesc';
	}

	/**
	 * @inheritDoc
	 */
	public function render( Parser $parser, PPFrame $frame, array $params ): string {
		$params = new ParameterParser( $frame, $params, [
			0 => [ 'unescape' => true ]
		] );

		$text = trim( $params->get( 0 ) );

		return ParserPower::evaluateUnescaped( $parser, $frame, $text );
	}
}
