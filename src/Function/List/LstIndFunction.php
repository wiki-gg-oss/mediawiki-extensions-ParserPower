<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function\List;

use MediaWiki\Extension\ParserPower\ListFunctions;
use MediaWiki\Extension\ParserPower\ParameterParser;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;
use MediaWiki\Extension\ParserPower\Function\ParserFunction;

/**
 * Parser function for searching a list value index (#lstind).
 */
final class LstIndFunction implements ParserFunction {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'lstind';
	}

	/**
	 * @inheritDoc
	 */
	public function render( Parser $parser, PPFrame $frame, array $params ): string {
		$params = new ParameterParser( $frame, $params, [
			[ 'unescape' => true ],
			ListFunctions::PARAM_OPTIONS['list'],
			ListFunctions::PARAM_OPTIONS['insep'],
			[]
		] );

		$list = $params->get( 1 );

		if ( $list === '' ) {
			return '';
		}

		$item = $params->get( 0 );
		$sep = $params->get( 2 );
		$sep = $parser->getStripState()->unstripNoWiki( $sep );
		$options = ListFunctions::decodeIndexOptions( $params->get( 3 ) );

		$values = ListFunctions::explodeList( $sep, $list );
		$count = ( is_array( $values ) || $values instanceof Countable ) ? count( $values ) : 0;
		if ( $options & ListFunctions::INDEX_DESC ) {
			if ( $options & ListFunctions::INDEX_CS ) {
				for ( $index = $count - 1; $index > -1; --$index ) {
					if ( $values[$index] === $item ) {
						return (string)( ( $options & ListFunctions::INDEX_NEG ) ? $index - $count : $index + 1 );
					}
				}
			} else {
				for ( $index = $count - 1; $index > -1; --$index ) {
					if ( strtolower( $values[$index] ) === strtolower( $item ) ) {
						return (string)( ( $options & ListFunctions::INDEX_NEG ) ? $index - $count : $index + 1 );
					}
				}
			}
		} else {
			if ( $options & ListFunctions::INDEX_CS ) {
				for ( $index = 0; $index < $count; ++$index ) {
					if ( $values[$index] === $item ) {
						return (string)( ( $options & ListFunctions::INDEX_NEG ) ? $index - $count : $index + 1 );
					}
				}
			} else {
				for ( $index = 0; $index < $count; ++$index ) {
					if ( strtolower( $values[$index] ) === strtolower( $item ) ) {
						return (string)( ( $options & ListFunctions::INDEX_NEG ) ? $index - $count : $index + 1 );
					}
				}
			}
		}
		return '';
	}
}
