<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function;

use MediaWiki\Extension\ParserPower\Parameters;
use MediaWiki\Extension\ParserPower\ParserPower;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

/**
 * Parser function for replacing a token in a pattern (#token).
 */
final class TokenFunction extends ParserFunctionBase {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'token';
	}

	/**
	 * @inheritDoc
	 */
	public function getParamSpec(): array {
		return [
			0 => [],
			1 => [ 'default' => 'x', 'unescape' => true ],
			2 => [ 'default' => 'x', 'novars' => true ]
		];
	}

	/**
	 * @inheritDoc
	 */
	public function execute( Parser $parser, PPFrame $frame, Parameters $params ): string {
		$inValue = $params->get( 0 );
		$token = $params->get( 1 );
		$pattern = $params->get( 2 );

		if ( $pattern === '' ) {
			$outValue = $inValue;
		} else {
			$outValue = str_replace( $token, $inValue, $pattern );
		}

		$outValue = $parser->preprocessToDom( $outValue, $frame->isTemplate() ? Parser::PTD_FOR_INCLUSION : 0 );
		$outValue = ParserPower::expand( $frame, $outValue, ParserPower::UNESCAPE );

		return ParserPower::evaluateUnescaped( $parser, $frame, $outValue );
	}
}
