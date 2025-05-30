<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower;

use MediaWiki\Parser\PPFrame;

/**
 * Factory class to create ParameterParser objects.
 */
final class ParameterParserFactory {

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
	 * Returns a new factory using different options.
	 *
	 * @param array $paramOptions Parsing and post-processing options for all parameters.
	 * @param array $defaultOptions Parsing and post-processing options for unknown parameters.
	 * @param int $flags Parameter parser flags.
	 * @return ParameterParserFactory The new factory.
	 */
	public function withOptions( array $paramOptions, array $defaultOptions = [], int $flags = 0 ): ParameterParserFactory {
		return new ParameterParserFactory( $paramOptions, $defaultOptions, $flags );
	}

	/**
	 * Create a parameter parser.
	 *
	 * @param PPFrame $frame Parser frame object.
	 * @param array $rawParams Unexpanded parameters.
	 * @return ParameterParser A parameter parser.
	 */
	public function newParameterParser( PPFrame $frame, array $rawParams ): ParameterParser {
		return new ParameterParser( $frame, $rawParams, $this->paramOptions, $this->defaultOptions, $this->flags );
	}
}
