<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower;

use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;
use MediaWiki\Parser\PPNode;

/**
 * Parameter parser for parser functions.
 *
 * Each parameter can be provided parsing and post-processing options. This includes all options recognized by the Parameters
 * class (except value, which is generated automatically). Additional options are:
 * - alias (string): name of another parameter to override.
 *
 * If a parameter name (string) is used directly as options, it overrides the specified parameter using the same options.
 */
final class ParameterParser {

	/**
	 * Flag for whether named arguments are allowed, and should be split from numbered arguments.
	 */
	public const ALLOWS_NAMED = 1;
	/**
	 * Flag for tracking parameter values that contain an = at the root level, that may be confused as a parameter key.
	 * May be used for migration purpose before enabling ALLOWS_NAMED.
	 */
	public const TRACKS_NAMED_VALUES = 2;

	/**
	 * @param array $paramOptions Parsing and post-processing options for all parameters.
	 * @param ?array $defaultOptions Parsing and post-processing options for unknown parameters if allowed.
	 * @param int $flags Parameter parser flags.
	 */
	public function __construct(
		private array $paramOptions = [],
		private ?array $defaultOptions = null,
		private int $flags = 0
	) {
	}

	/**
	 * Split a parameter key from its value.
	 *
	 * @param string|PPNode $param Parameter to split.
	 * @return array Pair with the parameter key (if named) and value.
	 */
	private function splitKeyValue( string|PPNode $param ): array {
		if ( is_string( $param ) ) {
			$pair = explode( '=', $param, 2 );
			$key = isset( $pair[1] ) ? array_shift( $pair ) : null;
			$value = $pair[0];
		} else {
			$bits = $param->splitArg();
			$key = $bits['index'] === '' ? $bits['name'] : null;
			$value = $bits['value'];
		}

		return [ $key, $value ];
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
				[ $key, $value ] = $this->splitKeyValue( $rawParam );
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

			if ( isset( $this->paramOptions[$key] ) ) {
				$options = $this->paramOptions[$key];
			} elseif ( $this->defaultOptions !== null ) {
				$options = $this->defaultOptions;
			} else {
				$parser->addTrackingCategory( 'parserpower-invalid-args-category' );
				continue;
			}

			if ( $key === null && $this->flags & self::TRACKS_NAMED_VALUES ) {
				try {
					[ $subKey, ] = $this->splitKeyValue( $value );
				} catch ( InvalidArgumentException ) {
					// Not a parameter, probably not an issue.
					$subKey = null;
				}
				if ( $subKey !== null ) {
					$parser->addTrackingCategory( 'parserpower-invalid-args-category' );
				}
			}

			// Resolve aliases
			if ( is_string( $options ) ) {
				$key = $options;
				$options = $this->paramOptions[$key];
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

		return new Parameters( $parser, $frame, $params, $this->defaultOptions['default'] ?? null );
	}
}
