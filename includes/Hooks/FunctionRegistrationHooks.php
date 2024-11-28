<?php

namespace MediaWiki\Extension\ParserPower\Hooks;

use MediaWiki\Extension\ParserPower\ParserPowerLists;
use MediaWiki\Extension\ParserPower\ParserPowerSimple;
use Parser;

final class FunctionRegistrationHooks implements
    \MediaWiki\Hook\ParserFirstCallInitHook
{
	/**
	 * Register ParserPower functions when the parser is initialised.
	 *
	 * @param Parser $parser Parser object being initialised
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onParserFirstCallInit( $parser ) {
		ParserPowerSimple::setup( $parser );
		ParserPowerLists::setup( $parser );
    }
}
