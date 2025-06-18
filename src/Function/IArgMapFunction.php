<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function;

use MediaWiki\Extension\ParserPower\Operation\TemplateOperation;
use MediaWiki\Extension\ParserPower\ParameterParser;
use MediaWiki\Extension\ParserPower\ParserPower;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

final class IArgMapFunction extends ParserFunctionBase {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'iargmap';
	}

	/**
	 * @inheritDoc
	 */
	public function getParamSpec(): array {
		return [
			...ArgMapFunction::PARAM_OPTIONS,
			0 => 'formatter',
			1 => 'n',
			2 => 'glue'
		];
	}

	/**
	 * @inheritDoc
	 */
	public function execute( Parser $parser, PPFrame $frame, ParameterParser $params ): string {
		// set parameters
		$formatter = $params->get( 'formatter' );
		if ( $formatter === '' ) {
			return ParserPower::errorMessage( 'iargmap', 'missing-parameter', 'formatter' );
		}

		$numberOfArgumentsPerFormatter = $params->get( 'n' );
		if ( $numberOfArgumentsPerFormatter === '' ) {
			return ParserPower::errorMessage( 'iargmap', 'missing-parameter', 'n' );
		}
		if ( !is_numeric( $numberOfArgumentsPerFormatter ) ) {
			return ParserPower::errorMessage( 'iargmap', 'invalid-integer', 'n' );
		}
		if ( intval( $numberOfArgumentsPerFormatter ) != floatval( $numberOfArgumentsPerFormatter ) ) {
			return ParserPower::errorMessage( 'iargmap', 'invalid-integer', 'n' );
		}

		$allFormatterArgs = $frame->getNumberedArguments();
		if ( count( $allFormatterArgs ) == 0 ) {
			return ParserPower::errorMessage( 'iargmap', 'no-arguments', 'n' );
		}

		$imax = count( $allFormatterArgs ) / intval( $numberOfArgumentsPerFormatter );
		if ( !is_int( $imax ) ) {
			return ParserPower::errorMessage( 'iargmap', 'invalid-argument-number', 'n' );
		}

		$glue = $params->get( 'glue' );

		// write formatter calls
		$operation = new TemplateOperation( $parser, $frame, $formatter );
		$formatterCalls = [];
		for ( $i = 0; $i < $imax; $i++ ) {
			$formatterArgs = [];
			for ( $n = 0; $n < $numberOfArgumentsPerFormatter; $n++ ) {
				$formatterArgs[] = $allFormatterArgs[$i * $numberOfArgumentsPerFormatter + $n + 1];
			}

			// parse formatter call
			$formatterCalls[] = trim( $operation->apply( $formatterArgs ) );
		}

		// proper '\n' handling
		$glue = str_replace( '\n', "\n", $glue );
		return implode( $glue, $formatterCalls );
	}
}
