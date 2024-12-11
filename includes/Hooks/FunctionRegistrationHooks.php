<?php

namespace MediaWiki\Extension\ParserPower\Hooks;

use MediaWiki\Extension\ParserPower\ListFunctions;
use MediaWiki\Extension\ParserPower\SimpleFunctions;
use Parser;

final class FunctionRegistrationHooks implements
	\MediaWiki\Hook\ParserFirstCallInitHook
{
	/**
	 * Register ParserPower functions when the parser is initialised.
	 *
	 * @param Parser $parser Parser object being initialised
	 * @return void
	 */
	public function onParserFirstCallInit( $parser ) {
		SimpleFunctions::setup( $parser );
		ListFunctions::setup( $parser );
	}
}
