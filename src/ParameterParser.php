<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower;

use MediaWiki\Parser\PPFrame;

/**
 * Parameter parser for parser functions.
 * Only evaluates parameter values on use site, and applies some post-processing steps to parsed values,
 * such as trimming whitespaces (as per longstanding MediaWiki conventions).
 */
final class ParameterParser {

	/**
	 * Flag for whether named arguments are allowed, and should be split from numbered arguments.
	 */
	public const ALLOWS_NAMED = 1;

	/**
	 * @var array Unexpanded parameters.
	 */
	private array $params = [];
	/**
	 * @var array Expanded (and post-processed) parameters.
	 */
	private array $expandedParams = [];

	/**
	 * @param PPFrame $frame Parser frame object.
	 * @param array $rawParams Unexpanded parameters.
	 * @param array $paramOptions Parsing and post-processing options for all parameters.
	 * @param array $defaultOptions Parsing and post-processing options for unknown parameters.
	 * @param int $flags Parameter parser flags.
	 */
	public function __construct(
		private readonly PPFrame $frame,
		private array $rawParams,
		private array $paramOptions = [],
		private array $defaultOptions = [],
		int $flags = 0
	) {
		$numberedCount = 0;

		foreach ( $rawParams as $rawParam ) {
			if ( $flags & self::ALLOWS_NAMED ) {
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

			if ( $key !== null ) {
				$key = ParserPower::expand( $frame, $key );
			} else {
				$key = $numberedCount++;
			}

			$this->params[$key] = $value;
		}
	}

	/**
	 * Check whether a parameter is defined, without evaluating it.
	 *
	 * @param int|string $key Parameter index or name.
	 * @return bool True if the parameter is defined, false otherwise.
	 */
	public function isDefined( int|string $key ): bool {
		return isset( $this->params[$key] );
	}

	/**
	 * Get the expanded value of a parameter.
	 *
	 * @param int|string $key Parameter index or name.
	 * @param array $options Parsing and post-processing options, overriding the default ones if it has not already been parsed.
	 * @return string The expanded (and post-processed) parameter value.
	 */
	public function get( int|string $key, array $options = [] ): string {
		if ( isset( $this->expandedParams[$key] ) ) {
			return $this->expandedParams[$key];
		}

		$options = array_merge( $this->paramOptions[$key] ?? $this->defaultOptions, $options );

		if ( !isset( $this->params[$key] ) ) {
			$value = $options['default'] ?? '';
		} else {
			$flags = 0;
			if ( $options['unescape'] ?? false ) {
				$flags |= ParserPower::UNESCAPE;
			}
			if ( $options['novars'] ?? false ) {
				$flags |= ParserPower::NO_VARS;
			}

			$value = ParserPower::expand( $this->frame, $this->params[$key], $flags );
		}

		$this->expandedParams[$key] = $value;
		return $value;
	}
}
