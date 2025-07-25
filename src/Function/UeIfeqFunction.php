<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function;

use MediaWiki\Extension\ParserPower\Parameters;
use MediaWiki\Extension\ParserPower\ParserPower;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

/**
 * Parser function for #ifeq with unescaped parameters (#ueifeq).
 */
final class UeIfeqFunction extends ParserFunctionBase {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'ueifeq';
	}

	/**
	 * @inheritDoc
	 */
	public function getParamSpec(): array {
		return [
			0 => [ 'unescape' => true ],
			1 => [ 'unescape' => true ],
			2 => [ 'unescape' => true ],
			3 => [ 'unescape' => true ]
		];
	}

	/**
	 * @inheritDoc
	 */
	public function execute( Parser $parser, PPFrame $frame, Parameters $params ): string {
		if ( $params->get( 0 ) === $params->get( 1 ) ) {
			$value = $params->get( 2 );
		} else {
			$value = $params->get( 3 );
		}

		return ParserPower::evaluateUnescaped( $parser, $frame, $value );
	}
}
