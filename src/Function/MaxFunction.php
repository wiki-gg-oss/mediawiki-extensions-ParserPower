<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function;

use MediaWiki\Extension\ParserPower\Formatter\FloatFormatter;
use MediaWiki\Extension\ParserPower\Parameters;
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
			'formatter' => new FloatFormatter(),
			'default' => NAN,
		];
	}

	/**
	 * @inheritDoc
	 */
	public function execute( Parser $parser, PPFrame $frame, Parameters $params ): string {
		/** @var ?float */
		$retval = null;

		for ( $i = 0; $params->isDefined( $i ); ++$i ) {
			$inValue = $params->get( $i );
			if ( $inValue === 'NAN' ) {
				continue;
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
