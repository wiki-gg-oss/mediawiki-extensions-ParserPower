<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function\List;

use MediaWiki\Extension\ParserPower\ListUtils;
use MediaWiki\Extension\ParserPower\Parameters;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

/**
 * Parser function for searching a list value index (#lstind).
 */
final class LstIndFunction extends ListFunction {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'lstind';
	}

	/**
	 * @inheritDoc
	 */
	public function getParamSpec(): array {
		return [
			...parent::getParamSpec(),
			0 => 'value',
			1 => 'list',
			2 => 'insep',
			3 => 'indexoptions'
		];
	}

	/**
	 * @inheritDoc
	 */
	public function execute( Parser $parser, PPFrame $frame, Parameters $params ): string {
		$list = $params->get( 'list' );
		$sep = $list !== '' ? $params->get( 'insep' ) : '';
		$sep = $parser->getStripState()->unstripNoWiki( $sep );
		$values = ListUtils::explode( $sep, $list );

		$count = count( $values );
		if ( $count === 0 ) {
			return '';
		}

		$item = $params->get( 'value' );

		$options = $params->get( 'indexoptions' );
		if ( $options & self::INDEX_DESC ) {
			if ( $options & self::INDEX_CS ) {
				for ( $index = $count - 1; $index > -1; --$index ) {
					if ( $values[$index] === $item ) {
						return (string)( ( $options & self::INDEX_NEG ) ? $index - $count : $index + 1 );
					}
				}
			} else {
				for ( $index = $count - 1; $index > -1; --$index ) {
					if ( strtolower( $values[$index] ) === strtolower( $item ) ) {
						return (string)( ( $options & self::INDEX_NEG ) ? $index - $count : $index + 1 );
					}
				}
			}
		} else {
			if ( $options & self::INDEX_CS ) {
				for ( $index = 0; $index < $count; ++$index ) {
					if ( $values[$index] === $item ) {
						return (string)( ( $options & self::INDEX_NEG ) ? $index - $count : $index + 1 );
					}
				}
			} else {
				for ( $index = 0; $index < $count; ++$index ) {
					if ( strtolower( $values[$index] ) === strtolower( $item ) ) {
						return (string)( ( $options & self::INDEX_NEG ) ? $index - $count : $index + 1 );
					}
				}
			}
		}
		return '';
	}
}
