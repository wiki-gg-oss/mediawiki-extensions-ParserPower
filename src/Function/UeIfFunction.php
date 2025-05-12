<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function;

use MediaWiki\Extension\ParserPower\ParameterParser;
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
		$params = new ParameterParser( $frame, $params, [
			0 => [],
			1 => [ 'unescape' => true ],
			2 => [ 'unescape' => true ]
		] );

		if ( $params->get( 0 ) !== '' ) {
			$value = $params->get( 1 );
		} else {
			$value = $params->get( 2 );
		}

		return ParserPower::evaluateUnescaped( $parser, $frame, $value );
	}
}
