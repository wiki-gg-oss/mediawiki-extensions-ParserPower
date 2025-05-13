<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function;

use MediaWiki\Extension\ParserPower\ParameterParser;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

/**
 * Parser function for returning the 1st non-empty value (#or).
 */
final class OrFunction extends ParserFunctionBase {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'or';
	}

	/**
	 * @inheritDoc
	 */
	public function getDefaultSpec(): array {
		return [];
	}

	/**
	 * @inheritDoc
	 */
	public function execute( Parser $parser, PPFrame $frame, ParameterParser $params ): string {
		for ( $i = 0; $params->isDefined( $i ); ++$i ) {
			$inValue = $params->get( $i );

			if ( $inValue !== '' ) {
				return $inValue;
			}
		}

		return '';
	}
}
