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
		return new Parameters( $parser, $frame, $rawParams, $this->paramOptions, $this->defaultOptions, $this->flags );
	}
}
