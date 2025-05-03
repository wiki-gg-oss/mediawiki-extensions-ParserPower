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
	 * @param array $params Unexpanded parameters.
	 * @param array $paramOptions Parsing and post-processing options for all parameters.
	 */
	public function __construct(
		private readonly PPFrame $frame,
		array $params,
		private array $paramOptions = []
	) {
		$this->params = self::arrange( $frame, $params );
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

	/**
	 * Arranges parser function parameters, separating named from numbered parameters.
	 *
	 * @param PPFrame $frame Parser frame object.
	 * @param array $params Unexpanded parameters.
	 */
	public static function arrange( PPFrame $frame, array $params ): array {
		$arrangedParams = [];
		$numberedCount = 0;

		foreach ( $params as $param ) {
			if ( is_string( $param ) ) {
				$pair = explode( '=', $param, 2 );
				if ( isset( $pair[1] ) ) {
					$key = array_shift( $pair );
				}
				$value = $pair[0];
			} else {
				$bits = $param->splitArg();
				if ( $bits['index'] === '' ) {
					$key = $bits['name'];
				}
				$value = $bits['value'];
			}

			$key = isset( $key ) ? ParserPower::expand( $frame, $key ) : $numberedCount++;

			$arrangedParams[$key] = $value;
		}

		return $arrangedParams;
	}
}
