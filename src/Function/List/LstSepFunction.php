<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function\List;

use MediaWiki\Extension\ParserPower\ListUtils;
use MediaWiki\Extension\ParserPower\Parameters;
use MediaWiki\Extension\ParserPower\ParserPower;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

/**
 * Parser function for replacing the value separator of a list (#lstsep).
 */
final class LstSepFunction extends ListFunction {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'lstsep';
	}

	/**
	 * @inheritDoc
	 */
	public function getParamSpec(): array {
		return [
			...parent::getParamSpec(),
			0 => 'list',
			1 => 'insep',
			2 => 'outsep',
			3 => 'outconj'
		];
	}

	/**
	 * @inheritDoc
	 */
	public function execute( Parser $parser, PPFrame $frame, Parameters $params ): string {
		$inList = $params->get( 'list' );
		$inSep = $inList !== '' ? $params->get( 'insep' ) : '';
		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );
		$values = ListUtils::explode( $inSep, $inList );

		return ParserPower::evaluateUnescaped( $parser, $frame, $this->implodeOutList( $params, $values ) );
	}
}
