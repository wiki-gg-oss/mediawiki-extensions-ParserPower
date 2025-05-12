<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function;

use MediaWiki\Extension\ParserPower\ParameterParser;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

/**
 * Parser function for returning the 1st non-empty value (#or).
 */
final class OrFunction implements ParserFunction {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'or';
	}

	/**
	 * @inheritDoc
	 */
	public function render( Parser $parser, PPFrame $frame, array $params ): string {
		$params = new ParameterParser( $frame, $params );

		for ( $i = 0; $params->isDefined( $i ); ++$i ) {
			$inValue = $params->get( $i );

			if ( $inValue !== '' ) {
				return $inValue;
			}
		}

		return '';
	}
}
