<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function\List;

use MediaWiki\Extension\ParserPower\Function\ParserFunctionBase;

/**
 * Parser function manipulating a list.
 */
abstract class ListFunction extends ParserFunctionBase {

	/**
	 * @inheritDoc
	 */
	public function getParamSpec(): array {
		return ListUtils::PARAM_OPTIONS;
	}
}
