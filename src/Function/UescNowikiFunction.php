<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function;

use MediaWiki\Extension\ParserPower\ParameterParser;
use MediaWiki\Extension\ParserPower\ParserPower;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

/**
 * Parser function for unescaping then wrapping in <nowiki> tags a value (#uescnowiki),
 * so that it isn't parsed.
 */
final class UescNowikiFunction implements ParserFunction {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'uescnowiki';
	}

	/**
	 * @inheritDoc
	 */
	public function render( Parser $parser, PPFrame $frame, array $params ): string {
		$params = new ParameterParser( $frame, $params, [
			0 => [ 'unescape' => true ]
		] );

		return ParserPower::evaluateUnescaped( $parser, $frame, '<nowiki>' . $params->get( 0 ) . '</nowiki>' );
	}
}
