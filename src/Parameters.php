<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower;

use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

/**
 * Parameter wrapper for parser functions.
 * Only evaluates parameter values on use site, and applies some post-processing steps to parsed values,
 * such as trimming whitespaces (as per longstanding MediaWiki conventions).
 *
 * Each parameter is an array with the following optional fields:
 * - default (mixed): expanded and post-processed value to use if value is unspecified.
 * - value (PPNode|string): unexpanded parameter value.
 * - novars (bool): variable syntax should not be expanded.
 * - unescape (bool): wikitext should be unescaped once expanded.
 */
final class Parameters {

	/**
	 * Expanded (and post-processed) parameters.
	 *
	 * @var array
	 */
	private array $expandedParams = [];

	/**
	 * @param Parser $parser Parser object.
	 * @param PPFrame $frame Parser frame object.
	 * @param array $params Unexpanded value with parsing options for every known parameter.
	 * @param mixed $default Default value for unknown parameters.
	 */
	public function __construct(
		private readonly Parser $parser,
		private readonly PPFrame $frame,
		private array $params,
		private $default = null
	) {
	}

	/**
	 * Check whether a parameter is defined, without evaluating it.
	 *
	 * @param int|string $key Parameter index or name.
	 * @return bool True if the parameter is defined, false otherwise.
	 */
	public function isDefined( int|string $key ): bool {
		return isset( $this->params[$key]['value'] );
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

		$options = array_merge( [ 'default' => $this->default ], $this->params[$key], $options );

		$formatter = $options['formatter'] ?? null;
		$default = $options['default'] ?? $formatter?->getDefault() ?? '';
		$value = $options['value'] ?? null;

		if ( $value === null ) {
			$this->expandedParams[$key] = $default;
			return $default;
		}

		$flags = 0;
		if ( $options['unescape'] ?? false ) {
			$flags |= ParserPower::UNESCAPE;
		}
		if ( $options['novars'] ?? false ) {
			$flags |= ParserPower::NO_VARS;
		}

		$value = ParserPower::expand( $this->frame, $value, $flags );
		if ( $formatter ) {
			$value = $formatter->format( $value, $default );
		}

		$this->expandedParams[$key] = $value;
		return $value;
	}
}
