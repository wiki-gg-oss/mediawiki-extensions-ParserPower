<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function\List;

use MediaWiki\Extension\ParserPower\ListUtils;
use MediaWiki\Extension\ParserPower\Operation\ListInclusionOperation;
use MediaWiki\Extension\ParserPower\ParameterParser;
use MediaWiki\Extension\ParserPower\ParserPower;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

/**
 * Parser function for filtering list values from an inclusion list (#lstfltr).
 */
final class LstFltrFunction extends ListFilterFunction {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'lstfltr';
	}

	/**
	 * @inheritDoc
	 */
	public function allowsNamedParams(): bool {
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function getParamSpec(): array {
		return [
			ListUtils::PARAM_OPTIONS['keep'],
			ListUtils::PARAM_OPTIONS['keepsep'],
			ListUtils::PARAM_OPTIONS['list'],
			ListUtils::PARAM_OPTIONS['insep'],
			ListUtils::PARAM_OPTIONS['outsep'],
			[]
		];
	}

	/**
	 * @inheritDoc
	 */
	public function execute( Parser $parser, PPFrame $frame, ParameterParser $params ): string {
		$inList = $params->get( 2 );

		if ( $inList === '' ) {
			return '';
		}

		$values = $params->get( 0 );
		$valueSep = $params->get( 1 );
		$inSep = $params->get( 3 );
		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );
		$outSep = $params->get( 4 );
		$csOption = ListUtils::decodeCSOption( $params->get( 5 ) );

		$inValues = ListUtils::explode( $inSep, $inList );

		if ( $valueSep !== '' ) {
			$values = ListUtils::explode( $valueSep, $values );
		} else {
			$values = [ ParserPower::unescape( $values ) ];
		}

		$operation = new ListInclusionOperation( $values, '', 'remove', $csOption );
		$outValues = $this->filterList( $operation, $inValues );

		if ( count( $outValues ) > 0 ) {
			return ParserPower::evaluateUnescaped( $parser, $frame, ListUtils::implode( $outValues, $outSep ) );
		} else {
			return '';
		}
	}
}
