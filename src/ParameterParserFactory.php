<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower;

use MediaWiki\Parser\Parser;
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
	 * Create a parameter parser.
	 *
	 * @param Parser $parser Parser object.
	 * @param PPFrame $frame Parser frame object.
	 * @param array $rawParams Unexpanded parameters.
	 * @return ParameterParser A parameter parser.
	 */
	public function newParameterParser( Parser $parser, PPFrame $frame, array $rawParams ): ParameterParser {
		return new ParameterParser( $parser, $frame, $rawParams, $this->paramOptions, $this->defaultOptions, $this->flags );
	}
}
