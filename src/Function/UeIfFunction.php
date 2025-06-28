<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function;

use MediaWiki\Extension\ParserPower\Parameters;
use MediaWiki\Extension\ParserPower\ParserPower;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

/**
 * Parser function for #if with unescaped parameters (#ueif).
 */
final class UeIfFunction extends ParserFunctionBase {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'ueif';
	}

	/**
	 * @inheritDoc
	 */
	public function getParamSpec(): array {
		return [
			0 => [],
			1 => [ 'unescape' => true ],
			2 => [ 'unescape' => true ]
		];
	}

	/**
	 * @inheritDoc
	 */
	public function execute( Parser $parser, PPFrame $frame, Parameters $params ): string {
		if ( $params->get( 0 ) !== '' ) {
			$value = $params->get( 1 );
		} else {
			$value = $params->get( 2 );
		}

		return ParserPower::evaluateUnescaped( $parser, $frame, $value );
	}
}
