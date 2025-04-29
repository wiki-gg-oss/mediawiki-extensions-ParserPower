<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower;

use MediaWiki\Parser\PPFrame;

/**
 * Named parameter arranger for parser functions.
 * Only evaluates parameter valuyes on use site, and applies some post-processings to parsed values,
 * such as trimming whitespaces (as per longstanding MediaWiki conventions).
 */
final class ParameterArranger {

	/**
	 * Unexpanded parameters.
	 */
	private array $params;
	/**
	 * Expanded (and post-processed) parameters.
	 */
	private array $expandedParams = [];

	/**
	 * @param PPFrame $frame Parser frame object.
	 * @param array $params Parameters and values together, not yet expanded or trimmed.
	 * @param array $paramOptions Parsing and post-processing options for all parameters.
	 */
	public function __construct(
		private readonly PPFrame $frame,
		array $params,
		private array $paramOptions = []
	) {
		$this->params = [];

		if ( isset( $params[0] ) && is_string( $params[0] ) ) {
			$pair = explode( '=', array_shift( $params ), 2 );
			if ( count( $pair ) === 2 ) {
				$key = ParserPower::expand( $this->frame, $pair[0] );
				$this->params[$key] = $pair[1];
			} else {
				$this->params[] = $pair[0];
			}
		}

		foreach ( $params as $param ) {
			$bits = $param->splitArg();
			if ( $bits['index'] === '' ) {
				$key = ParserPower::expand( $this->frame, $bits['name'] );
				$this->params[$key] = $bits['value'];
			} else {
				$this->params[] = $bits['value'];
			}
		}
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

		$options = array_merge( $this->paramOptions[$key] ?? [], $options );

		if ( !isset( $this->params[$key] ) ) {
			$value = $options['default'] ?? '';
		} else {
			$flags = 0;
			if ( $options['unescape'] ?? false ) {
				$flags |= ParserPower::UNESCAPE;
			}

			$value = ParserPower::expand( $this->frame, $this->params[$key], $flags );
		}

		$this->expandedParams[$key] = $value;
		return $value;
	}
}
