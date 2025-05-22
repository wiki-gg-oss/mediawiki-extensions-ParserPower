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
 * Parser function for appending a value to a list (#lstapp).
 */
final class LstAppFunction extends ParserFunctionBase {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'lstapp';
	}

	/**
	 * @inheritDoc
	 */
	public function getParamSpec(): array {
		return [
			...ListUtils::PARAM_OPTIONS,
			0 => 'list',
			1 => 'insep',
			2 => [ 'unescape' => true ]
		];
	}

	/**
	 * @inheritDoc
	 */
	public function execute( Parser $parser, PPFrame $frame, ParameterParser $params ): string {
		$list = $params->get( 0 );
		$sep = $list !== '' ? $params->get( 1 ) : '';
		$sep = $parser->getStripState()->unstripNoWiki( $sep );
		$value = $params->get( 2 );
		$values = ListUtils::explode( $sep, $list );

		if ( $value !== '' ) {
			$values[] = $value;
		}

		return ParserPower::evaluateUnescaped( $parser, $frame, ListUtils::implode( $values, $sep ) );
	}
}
