<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function\List;

use MediaWiki\Extension\ParserPower\ListUtils;
use MediaWiki\Extension\ParserPower\ParameterParser;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;
use MediaWiki\Extension\ParserPower\Function\ParserFunctionBase;

/**
 * Parser function for counting values of a list (#lstcnt).
 */
final class LstCntFunction extends ParserFunctionBase {

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
			ListUtils::PARAM_OPTIONS['list'],
			ListUtils::PARAM_OPTIONS['insep']
		];
	}

	/**
	 * @inheritDoc
	 */
	public function execute( Parser $parser, PPFrame $frame, ParameterParser $params ): string {
		$list = $params->get( 0 );
		$sep = $list !== '' ? $params->get( 1 ) : '';
		$sep = $parser->getStripState()->unstripNoWiki( $sep );

		return (string)count( ListUtils::explode( $sep, $list ) );
	}
}
