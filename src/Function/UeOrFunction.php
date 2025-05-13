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
final class UeOrFunction extends ParserFunctionBase {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'ueor';
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
				$inValue = ParserPower::unescape( $inValue );
				return ParserPower::evaluateUnescaped( $parser, $frame, $inValue );
			}
		}

		return '';
	}
}
