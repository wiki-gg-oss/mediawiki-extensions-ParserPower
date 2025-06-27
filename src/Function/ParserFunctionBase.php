<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function;

use MediaWiki\Extension\ParserPower\ParameterParser;
use MediaWiki\Extension\ParserPower\ParameterParserFactory;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

/**
 * Parser function, using a ParameterParser to manage its parameters.
 */
abstract class ParserFunctionBase implements ParserFunction {

	private readonly ParameterParserFactory $paramsFactory;

	public function __construct() {
		$paramFlags = 0;
		if ( $this->allowsNamedParams() ) {
			$paramFlags |= ParameterParser::ALLOWS_NAMED;
		}

		$this->paramsFactory = new ParameterParserFactory( $this->getParamSpec(), $this->getDefaultSpec(), $paramFlags );
	}

	/**
	 * Whether named parameters are recognized, along with numbered parameters.
	 *
	 * @return bool
	 */
	public function allowsNamedParams(): bool {
		return false;
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
	 * @return array A parameter specification.
	 */
	public function getDefaultSpec(): array {
		return [];
	}

	/**
	 * Perform the operations of the function, based on what parameter values are provided.
	 *
	 * @param Parser $parser Parser object.
	 * @param PPFrame $frame Parser frame object.
	 * @param ParameterParser $params Arranged function parameters.
	 * @return string The function output.
	 */
	abstract public function execute( Parser $parser, PPFrame $frame, ParameterParser $params ): string;

	/**
	 * @inheritDoc
	 */
	public function render( Parser $parser, PPFrame $frame, array $params ): string {
		$params = $this->paramsFactory->newParameterParser( $parser, $frame, $params );
		return $this->execute( $parser, $frame, $params );
	}
}
