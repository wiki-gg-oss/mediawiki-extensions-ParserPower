<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function;

use MediaWiki\Extension\ParserPower\ParameterParser;
use MediaWiki\Extension\ParserPower\ParserPower;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

/**
 * Parser function for #or with unescaped parameters (#ueor).
 */
final class UeOrFunction implements ParserFunction {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'ueor';
	}

	/**
	 * @inheritDoc
	 */
	public function render( Parser $parser, PPFrame $frame, array $params ): string {
		$params = new ParameterParser( $frame, $params );

		for ( $i = 0; $params->isDefined( $i ); ++$i ) {
			$inValue = $params->get( $i );

			if ( $inValue !== '' ) {
				$inValue = ParserPower::unescape( $inValue );
				return ParserPower::evaluateUnescaped( $parser, $frame, $inValue );
			}
		}

		return '';
	}
}
