<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function;

use MediaWiki\Extension\ParserPower\ParserPower;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;
use MediaWiki\Parser\PPNode_Hash_Array;

final class IArgMapFunction implements ParserFunction {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'iargmap';
	}

	/**
	 * @inheritDoc
	 */
	public function render( Parser $parser, PPFrame $frame, array $args ): string {
		if ( !isset( $args[0] ) ) {
			return ParserPower::errorMessage( 'iargmap', 'missing-parameter', 'formatter' );
		}
		if ( !isset( $args[1] ) ) {
			return ParserPower::errorMessage( 'iargmap', 'missing-parameter', 'n' );
		}

		// set parameters
		$formatter = trim( $frame->expand( $args[0] ) );
		$numberOfArgumentsPerFormatter = trim( $frame->expand( $args[1] ) );
		$glue = isset( $args[2] ) ? trim( $frame->expand( $args[2] ) ) : ', ';
		$allFormatterArgs = $frame->getNumberedArguments();

		// check against bad entries
		if ( count( $allFormatterArgs ) == 0 ) {
			return ParserPower::errorMessage( 'iargmap', 'no-arguments', 'n' );
		}
		if ( !is_numeric( $numberOfArgumentsPerFormatter ) ) {
			return ParserPower::errorMessage( 'iargmap', 'invalid-integer', 'n' );
		}

		if ( intval( $numberOfArgumentsPerFormatter ) != floatval( $numberOfArgumentsPerFormatter ) ) {
			return ParserPower::errorMessage( 'iargmap', 'invalid-integer', 'n' );
		}

		$imax = count( $allFormatterArgs ) / intval( $numberOfArgumentsPerFormatter );

		if ( !is_int( $imax ) ) {
			return ParserPower::errorMessage( 'iargmap', 'invalid-argument-number', 'n' );
		}

		// write formatter calls
		$formatterCalls = [];
		for ( $i = 0; $i < $imax; $i++ ) {
			$formatterArgs = [];
			for ( $n = 0; $n < $numberOfArgumentsPerFormatter; $n++ ) {
				$formatterArgs[] = trim( $frame->expand( $allFormatterArgs[ $i * $numberOfArgumentsPerFormatter + $n + 1] ) );
			}

			$val = implode( '|', $formatterArgs );
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
