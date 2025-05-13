<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function;

use MediaWiki\Extension\ParserPower\ParameterParser;
use MediaWiki\Extension\ParserPower\ParserPower;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

/**
 * Parser function for unescaping a value (#uesc).
 */
final class UescFunction extends ParserFunctionBase {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'uesc';
	}

	/**
	 * @inheritDoc
	 */
	public function getParamSpec(): array {
		return [
			0 => [ 'unescape' => true ]
		];
	}

	/**
	 * @inheritDoc
	 */
	public function execute( Parser $parser, PPFrame $frame, ParameterParser $params ): string {
		return ParserPower::evaluateUnescaped( $parser, $frame, $params->get( 0 ) );
	}
}
