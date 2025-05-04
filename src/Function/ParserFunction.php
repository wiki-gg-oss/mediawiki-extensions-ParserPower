<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function;

use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

/**
 * Parser function.
 */
interface ParserFunction {

	/**
	 * Get the function name.
	 *
	 * @return string The function name.
	 */
	public function getName(): string;

	/**
	 * Performs the operations of the function, based on what parameter values are provided.
	 *
	 * @param Parser $parser Parser object.
	 * @param PPFrame $frame Parser frame object.
	 * @param array $params Unexpanded parameters.
	 * @return string The function output.
	 */
	public function render( Parser $parser, PPFrame $frame, array $params ): string;
}
