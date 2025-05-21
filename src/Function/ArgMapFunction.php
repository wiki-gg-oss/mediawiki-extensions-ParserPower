<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function;

use MediaWiki\Extension\ParserPower\ParserPower;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;
use MediaWiki\Parser\PPNode_Hash_Array;

final class ArgMapFunction implements ParserFunction {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'argmap';
	}

	/**
	 * @inheritDoc
	 */
	public function render( Parser $parser, PPFrame $frame, array $args ): string {
		if ( !isset( $args[0] ) ) {
			return ParserPower::errorMessage( 'argmap', 'missing-parameter', 'formatter' );
		}

		// set parameters
		$formatter = trim( $frame->expand( $args[0] ) );
		$glue = isset( $args[1] ) ? trim( $frame->expand( $args[1] ) ) : ', ';
		$mustContainString = isset( $args[2] ) ? trim( $frame->expand( $args[2] ) ) : '';
		$onlyShowString = isset( $args[3] ) ? trim( $frame->expand( $args[3] ) ) : '';
		$formatterArgs = $frame->getNamedArguments();

		// make arrays
		$mustContain = [];
		$onlyShow = [];
		if ( $mustContainString !== '' ) {
			$mustContain = explode( ',', $mustContainString );
		}
		if ( $onlyShowString !== '' ) {
			$onlyShow = explode( ',', $onlyShowString );
		}

		// group formatter arguments to groupedFormatterArgs array, if viable
		$groupedFormatterArgs = [];
		foreach ( $formatterArgs as $key => $arg ) {
			$index = preg_replace( '/[^0-9]/', '', $key );
			$argName = preg_replace( '/[^a-zA-Z]/', '', $key );

			if ( $index !== '' ) {
				$index = intval( $index );
				if ( !isset( $groupedFormatterArgs[$index] ) ) {
					$groupedFormatterArgs[$index] = [];
				}
				$groupedFormatterArgs[$index][$argName] = $arg;
			}
		}

		// write formatter calls, if viable
		$formatterCalls = [];
		foreach ( $groupedFormatterArgs as $formatterArg ) {
			// check if there are missing arguments
			$missingArgs = array_diff( $mustContain, array_keys( $formatterArg ) );
			if ( !empty( $missingArgs ) ) {
				continue;
			}

			// process individual args and filter for onlyShow
			$processedFormatterArg = [];
			foreach ( $formatterArg as $key => $value ) {
				if ( empty( $onlyShow ) || in_array( $key, $onlyShow ) ) {
					$processedFormatterArg[] = "$key=$value";
				}
			}

			// discard if nothing remains
			if ( empty( $processedFormatterArg ) ) {
				continue;
			}

			// construct final formatter call
			$val = implode( '|', $processedFormatterArg );
			$formatterCall = $frame->virtualBracketedImplode( '{{', '|', '}}', $formatter, $val );
			if ( $formatterCall instanceof PPNode_Hash_Array ) {
				$formatterCall = $formatterCall->value;
			}
			$formatterCall = implode( '', $formatterCall );

			// parse formatter call
			$formatterCalls[] = trim( $parser->replaceVariables( $formatterCall, $frame ) );
		}

		// proper '\n' handling
		$glue = str_replace( '\n', "\n", $glue );
		return implode( $glue, $formatterCalls );
	}
}
