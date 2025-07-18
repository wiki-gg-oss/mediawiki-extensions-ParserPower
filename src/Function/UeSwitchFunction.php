<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function;

use MediaWiki\Extension\ParserPower\ParserPower;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

/**
 * Parser function for #switch with unescaped parameters (#ueswitch).
 */
final class UeSwitchFunction implements ParserFunction {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'ueswitch';
	}

	/**
	 * @inheritDoc
	 */
	public function render( Parser $parser, PPFrame $frame, array $params ): string {
		$switchKey = isset( $params[0] ) ? ParserPower::expand( $frame, array_shift( $params ), ParserPower::UNESCAPE ) : '';

		if ( empty( $params ) ) {
			return '';
		}

		$default = '';
		$mwDefaultFound = false;
		$mwDefault = $parser->getMagicWordFactory()->get( 'default' );

		$keyFound = false;
		foreach ( $params as $param ) {
			$bits = $param->splitArg();
			if ( $bits['index'] === '' ) {
				$key = $bits['name'];
				$value = $bits['value'];
			} else {
				$key = $bits['value'];
				$value = null;
			}

			if ( !$keyFound ) {
				$key = ParserPower::expand( $frame, $key, ParserPower::UNESCAPE );
				if ( $key === $switchKey ) {
					$keyFound = true;
				} elseif ( $mwDefault->matchStartToEnd( $key ) ) {
					$mwDefaultFound = true;
				}
			}

			if ( $value !== null ) {
				if ( $keyFound ) {
					$value = ParserPower::expand( $frame, $value, ParserPower::UNESCAPE );
					return ParserPower::evaluateUnescaped( $parser, $frame, $value );
				} elseif ( $mwDefaultFound ) {
					$default = $value;
					$mwDefaultFound = false;
				}
			}
		}

		if ( $value === null ) {
			$default = is_string( $key ) ? $key : ParserPower::expand( $frame, $key, ParserPower::UNESCAPE );
		} else {
			$default = ParserPower::expand( $frame, $default, ParserPower::UNESCAPE );
		}
		return ParserPower::evaluateUnescaped( $parser, $frame, $default );
	}
}
