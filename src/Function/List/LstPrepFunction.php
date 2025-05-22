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
 * Parser function for prepending a value to a list (#lstprep).
 */
final class LstPrepFunction extends ParserFunctionBase {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'lstprep';
	}

	/**
	 * @inheritDoc
	 */
	public function getParamSpec(): array {
		return [
			...ListUtils::PARAM_OPTIONS,
			0 => [ 'unescape' => true ],
			1 => 'insep',
			2 => 'list'
		];
	}

	/**
	 * @inheritDoc
	 */
	public function execute( Parser $parser, PPFrame $frame, ParameterParser $params ): string {
		$value = $params->get( 0 );
		$list = $params->get( 2 );
		$sep = $list !== '' ? $params->get( 1 ) : '';
		$sep = $parser->getStripState()->unstripNoWiki( $sep );
		$values = ListUtils::explode( $sep, $list );

		if ( $value !== '' ) {
			array_unshift( $values, $value );
		}

		return ParserPower::evaluateUnescaped( $parser, $frame, ListUtils::implode( $values, $sep ) );
	}
}
