<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function\List;

use MediaWiki\Extension\ParserPower\ListUtils;
use MediaWiki\Extension\ParserPower\ParameterParser;
use MediaWiki\Extension\ParserPower\ParserPower;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;
use MediaWiki\Extension\ParserPower\Function\ParserFunctionBase;

/**
 * Parser function for searching a list value (#lstfnd).
 */
final class LstFndFunction extends ParserFunctionBase {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'lstfnd';
	}

	/**
	 * @inheritDoc
	 */
	public function getParamSpec(): array {
		return [
			...ListUtils::PARAM_OPTIONS,
			0 => 'value',
			1 => 'list',
			2 => 'insep',
			3 => 'csoption'
		];
	}

	/**
	 * @inheritDoc
	 */
	public function execute( Parser $parser, PPFrame $frame, ParameterParser $params ): string {
		$list = $params->get( 'list' );
		$sep = $list !== '' ? $params->get( 'insep' ) : '';
		$sep = $parser->getStripState()->unstripNoWiki( $sep );
		$values = ListUtils::explode( $sep, $list );

		if ( count( $values ) === 0 ) {
			return '';
		}

		$item = $params->get( 'value' );

		$csOption = $params->get( 'csoption' );
		$csOption = ListUtils::decodeCSOption( $csOption );
		if ( $csOption ) {
			foreach ( $values as $value ) {
				if ( $value === $item ) {
					return ParserPower::evaluateUnescaped( $parser, $frame, $value );
				}
			}
		} else {
			foreach ( $values as $value ) {
				if ( strtolower( $value ) === strtolower( $item ) ) {
					return ParserPower::evaluateUnescaped( $parser, $frame, $value );
				}
			}
		}
		return '';
	}
}
