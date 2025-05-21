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
			$message = ParserPower::newMessage( 'error-missing-parameter', 'iargmap', 'formatter' );
			return '<strong class="error">' . $message->inContentLanguage()->text() . '</strong>';
		}
		if ( !isset( $args[1] ) ) {
			$message = ParserPower::newMessage( 'error-missing-parameter', 'iargmap', 'n' );
			return '<strong class="error">' . $message->inContentLanguage()->text() . '</strong>';
		}

		// set parameters
		$formatter = trim( $frame->expand( $args[0] ) );
		$numberOfArgumentsPerFormatter = trim( $frame->expand( $args[1] ) );
		$glue = isset( $args[2] ) ? trim( $frame->expand( $args[2] ) ) : ', ';
		$allFormatterArgs = $frame->getNumberedArguments();

		// check against bad entries
		if ( count( $allFormatterArgs ) == 0 ) {
			$message = ParserPower::newMessage( 'error-no-arguments', 'iargmap' );
			return '<strong class="error">' . $message->inContentLanguage()->text() . '</strong>';
		}
		if ( !is_numeric( $numberOfArgumentsPerFormatter ) ) {
			$message = ParserPower::newMessage( 'error-invalid-integer', 'iargmap', 'n' );
			return '<strong class="error">' . $message->inContentLanguage()->text() . '</strong>';
		}

		if ( intval( $numberOfArgumentsPerFormatter ) != floatval( $numberOfArgumentsPerFormatter ) ) {
			$message = ParserPower::newMessage( 'error-invalid-integer', 'iargmap', 'n' );
			return '<strong class="error">' . $message->inContentLanguage()->text() . '</strong>';
		}

		$imax = count( $allFormatterArgs ) / intval( $numberOfArgumentsPerFormatter );

		if ( !is_int( $imax ) ) {
			$message = ParserPower::newMessage( 'error-invalid-argument-number', 'iargmap', 'n' );
			return '<strong class="error">' . $message->inContentLanguage()->text() . '</strong>';
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
