<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function;

use MediaWiki\Extension\ParserPower\Parameters;
use MediaWiki\Extension\ParserPower\ParserPower;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

/**
 * Parser function for finding the lowwest number (#min).
 */
final class MinFunction extends ParserFunctionBase {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'min';
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
				return ParserPower::errorMessage( 'min', 'invalid-number', $inValue );
			}

			$inValue = floatval( $inValue );

			if ( $retval === null ) {
				$retval = $inValue;
			} else {
				$retval = min( $retval, $inValue );
			}
		}

		return (string)( $retval ?? '' );
	}
}
