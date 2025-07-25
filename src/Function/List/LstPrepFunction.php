<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function\List;

use MediaWiki\Extension\ParserPower\ListUtils;
use MediaWiki\Extension\ParserPower\Parameters;
use MediaWiki\Extension\ParserPower\ParserPower;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

/**
 * Parser function for prepending a value to a list (#lstprep).
 */
final class LstPrepFunction extends ListFunction {

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
			...parent::getParamSpec(),
			0 => 'value',
			1 => 'insep',
			2 => 'list'
		];
	}

	/**
	 * @inheritDoc
	 */
	public function execute( Parser $parser, PPFrame $frame, Parameters $params ): string {
		$value = $params->get( 'value' );
		$list = $params->get( 'list' );
		$sep = $list !== '' ? $params->get( 'insep' ) : '';
		$sep = $parser->getStripState()->unstripNoWiki( $sep );
		$values = ListUtils::explode( $sep, $list );

		if ( $value !== '' ) {
			array_unshift( $values, $value );
		}

		return ParserPower::evaluateUnescaped( $parser, $frame, ListUtils::implode( $values, $sep ) );
	}
}
