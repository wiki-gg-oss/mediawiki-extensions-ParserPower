<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function\List;

use MediaWiki\Extension\ParserPower\ListUtils;
use MediaWiki\Extension\ParserPower\Parameters;
use MediaWiki\Extension\ParserPower\ParserPower;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

/**
 * Parser function for subdividing a list (#lstsub).
 */
final class LstSubFunction extends ListFunction {

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
			...parent::getParamSpec(),
			0 => 'list',
			1 => 'insep',
			2 => 'outsep',
			3 => 'index',
			4 => 'length'
		];
	}

	/**
	 * @inheritDoc
	 */
	public function execute( Parser $parser, PPFrame $frame, Parameters $params ): string {
		$inList = $params->get( 'list' );
		$inSep = $inList !== '' ? $params->get( 'insep' ) : '';
		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );
		$inValues = ListUtils::explode( $inSep, $inList );

		$inCount = count( $inValues );
		if ( $inCount === 0 ) {
			return '';
		}

		$offset = $params->get( 'index' );
		$offset = is_numeric( $offset ) ? intval( $offset ) : 0;
		$length = $offset < $inCount ? $params->get( 'length' ) : '';
		$length = is_numeric( $length ) ? intval( $length ) : null;
		$outValues = ListUtils::slice( $inValues, $offset, $length );

		$outSep = count( $outValues ) > 1 ? $params->get( 'outsep' ) : '';
		$outList = ListUtils::implode( $outValues, $outSep );

		return ParserPower::evaluateUnescaped( $parser, $frame, $outList );
	}
}
