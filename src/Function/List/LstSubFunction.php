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
 * Parser function for subdividing a list (#lstsub).
 */
final class LstSubFunction extends ParserFunctionBase {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'lstsub';
	}

	/**
	 * @inheritDoc
	 */
	public function getParamSpec(): array {
		return [
			...ListUtils::PARAM_OPTIONS,
			0 => 'list',
			1 => 'insep',
			2 => 'outsep',
			3 => [ 'unescape' => true ],
			4 => [ 'unescape' => true ]
		];
	}

	/**
	 * @inheritDoc
	 */
	public function execute( Parser $parser, PPFrame $frame, ParameterParser $params ): string {
		$inList = $params->get( 0 );
		$inSep = $inList !== '' ? $params->get( 1 ) : '';
		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );
		$inValues = ListUtils::explode( $inSep, $inList );

		$inCount = count( $inValues );
		if ( $inCount === 0 ) {
			return '';
		}

		$offset = $params->get( 3 );
		$offset = is_numeric( $offset ) ? intval( $offset ) : 0;
		$length = $offset < $inCount ? $params->get( 4 ) : '';
		$length = is_numeric( $length ) ? intval( $length ) : null;
		$outValues = ListUtils::slice( $inValues, $offset, $length );

		$outSep = count( $outValues ) > 1 ? $params->get( 2 ) : '';
		$outList = ListUtils::implode( $outValues, $outSep );

		return ParserPower::evaluateUnescaped( $parser, $frame, $outList );
	}
}
