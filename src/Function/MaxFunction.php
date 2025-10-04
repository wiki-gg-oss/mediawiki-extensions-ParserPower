<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function;

use MediaWiki\Extension\ParserPower\Parameters;
use MediaWiki\Extension\ParserPower\ParserPower;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

/**
 * Parser function for finding the highest number (#max).
 */
final class MaxFunction extends ParserFunctionBase {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'max';
	}

	/**
	 * @inheritDoc
	 */
	public function getDefaultSpec(): ?array {
		return [
			'unescape' => true,
		];
	}

	/**
	 * @inheritDoc
	 */
	public function execute( Parser $parser, PPFrame $frame, Parameters $params ): string {
		/** @var ?float */
		$retval = null;

		for ( $i = 0; $params->isDefined( $i ); ++$i ) {
			$inValue = trim( $params->get( $i ) );

			// Skip empty parameters
			if ( $inValue === '' ) {
				continue;
			}

			// Throw an error if the parameter is non-numeric
			if ( !is_numeric( $inValue ) ) {
				return ParserPower::errorMessage( 'max', 'invalid-number', $inValue );
			}

			if ( $retval === null ) {
				$retval = $inValue;
			} else {
				$retval = max( $retval, $inValue );
			}
		}

		return (string)( $retval ?? '' );
	}
}
