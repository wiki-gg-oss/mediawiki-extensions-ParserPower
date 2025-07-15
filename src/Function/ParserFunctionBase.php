<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function;

use MediaWiki\Extension\ParserPower\ParameterParser;
use MediaWiki\Extension\ParserPower\Parameters;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

/**
 * Parser function, using a Parameters to manage its parameters.
 */
abstract class ParserFunctionBase implements ParserFunction {

	private readonly ParameterParser $paramParser;

	public function __construct() {
		$this->paramParser = new ParameterParser( $this->getParamSpec(), $this->getDefaultSpec(), $this->getParserFlags() );
	}

	/**
	 * Get the set of flags to use with the parameter parser.
	 *
	 * @return int The set of ParameterParser flags.
	 */
	public function getParserFlags(): int {
		return 0;
	}

	/**
	 * Get the list of parameter-specific parsing and post-processing options.
	 *
	 * @return array The list of parameter specifications.
	 */
	public function getParamSpec(): array {
		return [];
	}

	/**
	 * Get the parsing and post-processing options to use with unknown parameters.
	 *
	 * @return ?array A parameter specification if unknown parameters are allowed.
	 */
	public function getDefaultSpec(): ?array {
		return null;
	}

	/**
	 * Perform the operations of the function, based on what parameter values are provided.
	 *
	 * @param Parser $parser Parser object.
	 * @param PPFrame $frame Parser frame object.
	 * @param Parameters $params Arranged function parameters.
	 * @return string The function output.
	 */
	abstract public function execute( Parser $parser, PPFrame $frame, Parameters $params ): string;

	/**
	 * @inheritDoc
	 */
	public function render( Parser $parser, PPFrame $frame, array $params ): string {
		$params = $this->paramParser->parse( $parser, $frame, $params );
		return $this->execute( $parser, $frame, $params );
	}
}
