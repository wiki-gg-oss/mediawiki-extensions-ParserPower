<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Hooks;

use MediaWiki\Extension\ParserPower\ParserVariableRegistry;
use MediaWiki\Parser\Parser;

/**
 * Hook handler for registering functions/tags with the legacy parser.
 */
final class FunctionRegistrationHooks implements
	\MediaWiki\Hook\ParserFirstCallInitHook
{

	/**
	 * @param ParserVariableRegistry $parserVariableRegistry
	 */
	public function __construct( private ParserVariableRegistry $parserVariableRegistry ) {
	}

	/**
	 * Register ParserPower functions when the parser is initialised.
	 *
	 * @param Parser $parser Parser object being initialised
	 * @return void
	 */
	public function onParserFirstCallInit( $parser ) {
		foreach ( $this->parserVariableRegistry->getFunctions() as $function ) {
			$parser->setFunctionHook( $function->getName(), [ $function, 'render' ], Parser::SFH_OBJECT_ARGS );
		}

		foreach ( $this->parserVariableRegistry->getTags() as $tag ) {
			foreach ( $tag->getNames() as $name ) {
				$parser->setHook( $name, [ $tag, 'render' ] );
			}
		}
	}
}
