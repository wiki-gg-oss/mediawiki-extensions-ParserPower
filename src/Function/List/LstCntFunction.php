<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function\List;

use MediaWiki\Extension\ParserPower\ListUtils;
use MediaWiki\Extension\ParserPower\Parameters;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

/**
 * Parser function for counting values of a list (#lstcnt).
 */
final class LstCntFunction extends ListFunction {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'lstcnt';
	}

	/**
	 * @inheritDoc
	 */
	public function getParamSpec(): array {
		return [
			...parent::getParamSpec(),
			0 => 'list',
			1 => 'insep'
		];
	}

	/**
	 * @inheritDoc
	 */
	public function execute( Parser $parser, PPFrame $frame, Parameters $params ): string {
		$list = $params->get( 'list' );
		$sep = $list !== '' ? $params->get( 'insep' ) : '';
		$sep = $parser->getStripState()->unstripNoWiki( $sep );

		return (string)count( ListUtils::explode( $sep, $list ) );
	}
}
