<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function;

use MediaWiki\Extension\ParserPower\Operation\TemplateOperation;
use MediaWiki\Extension\ParserPower\Parameters;
use MediaWiki\Extension\ParserPower\ParserPower;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

final class ArgMapFunction extends ParserFunctionBase {

	/**
	 * Parsing and post-processing options for #argmap-based function parameters.
	 */
	public const PARAM_OPTIONS = [
		'formatter' => [],
		'glue' => [ 'default' => ', ' ],
		'mustcontain' => [],
		'n' => [],
		'onlyshow' => []
	];

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'argmap';
	}

	/**
	 * @inheritDoc
	 */
	public function getParamSpec(): array {
		return [
			...self::PARAM_OPTIONS,
			0 => 'formatter',
			1 => 'glue',
			2 => 'mustcontain',
			3 => 'onlyshow'
		];
	}

	/**
	 * @inheritDoc
	 */
	public function execute( Parser $parser, PPFrame $frame, Parameters $params ): string {
		// set parameters
		$formatter = $params->get( 'formatter' );
		if ( $formatter === '' ) {
			return ParserPower::errorMessage( 'argmap', 'missing-parameter', 'formatter' );
		}

		$glue = $params->get( 'glue' );

		$mustContainString = $params->get( 'mustcontain' );
		$mustContain = $mustContainString !== '' ? explode( ',', $mustContainString ) : [];

		$onlyShowString = $params->get( 'onlyshow' );
		$onlyShow = $onlyShowString !== '' ? explode( ',', $onlyShowString ) : [];

		$formatterArgs = $frame->getNamedArguments();

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
		$operation = new TemplateOperation( $parser, $frame, $formatter );
		$formatterCalls = [];
		foreach ( $groupedFormatterArgs as $formatterArgs ) {
			// check if there are missing arguments
			$missingArgs = array_diff( $mustContain, array_keys( $formatterArgs ) );
			if ( !empty( $missingArgs ) ) {
				continue;
			}

			// process individual args and filter for onlyShow
			if ( !empty( $onlyShow ) ) {
				$formatterArgs = array_filter( $formatterArgs, fn ( $k ) => in_array( $k, $onlyShow ), ARRAY_FILTER_USE_KEY );
			}

			// discard if nothing remains
			if ( empty( $formatterArgs ) ) {
				continue;
			}

			// parse formatter call
			$formatterCalls[] = trim( $operation->apply( $formatterArgs ) );
		}

		// proper '\n' handling
		$glue = str_replace( '\n', "\n", $glue );
		return implode( $glue, $formatterCalls );
	}
}
