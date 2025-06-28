<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower;

use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

/**
 * Parameter parser for parser functions.
 */
final class ParameterParser {

	/**
	 * Flag for whether named arguments are allowed, and should be split from numbered arguments.
	 */
	public const ALLOWS_NAMED = 1;

	/**
	 * @param array $paramOptions Parsing and post-processing options for all parameters.
	 * @param array $defaultOptions Parsing and post-processing options for unknown parameters.
	 * @param int $flags Parameter parser flags.
	 */
	public function __construct(
		private array $paramOptions = [],
		private array $defaultOptions = [],
		private int $flags = 0
	) {
	}

	/**
	 * Create a parameter parser.
	 *
	 * @param Parser $parser Parser object.
	 * @param PPFrame $frame Parser frame object.
	 * @param array $rawParams Unexpanded parameters.
	 * @return Parameters A parameter parser.
	 */
	public function parse( Parser $parser, PPFrame $frame, array $rawParams ): Parameters {
		$numberedCount = 0;
		$params = $this->paramOptions;

		foreach ( $rawParams as $rawParam ) {
			// Split key from value
			if ( $this->flags & self::ALLOWS_NAMED ) {
				if ( is_string( $rawParam ) ) {
					$pair = explode( '=', $rawParam, 2 );
					$key = isset( $pair[1] ) ? array_shift( $pair ) : null;
					$value = $pair[0];
				} else {
					$bits = $rawParam->splitArg();
					$key = $bits['index'] === '' ? $bits['name'] : null;
					$value = $bits['value'];
				}
			} else {
				$key = null;
				$value = $rawParam;
			}

			// Expand key
			if ( $key !== null ) {
				$key = ParserPower::expand( $frame, $key );
			} else {
				$key = $numberedCount++;
			}

			// Resolve aliases
			$options = $this->paramOptions[$key] ?? $this->defaultOptions;
			if ( is_string( $options ) ) {
				$key = $options;
				$options = $this->paramOptions[$key] ?? $this->defaultOptions;
			}
			if ( isset( $options['alias'] ) ) {
				$key = $options['alias'];
			}

			if ( isset( $params[$key] ) ) {
				$parser->addTrackingCategory( 'parserpower-duplicate-args-category' );
			}

			$options['value'] = $value;
			$params[$key] = $options;
		}

		return new Parameters( $parser, $frame, $params );
	}
}
