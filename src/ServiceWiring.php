<?php

/** @license GPL-2.0-or-later */

use MediaWiki\Config\Config;
use MediaWiki\Extension\ParserPower\ParameterParserFactory;
use MediaWiki\Extension\ParserPower\ParserVariableRegistry;
use MediaWiki\MediaWikiServices;

return [
	'ParserPower.Config' => static function ( MediaWikiServices $services ): Config {
		return $services->getConfigFactory()->makeConfig( 'ParserPower' );
	},

	'ParserPower.ParameterParserFactory' => static function (): ParameterParserFactory {
		return new ParameterParserFactory();
	},

	'ParserPower.ParserVariableRegistry' => static function ( MediaWikiServices $services ): ParserVariableRegistry {
		return new ParserVariableRegistry( $services->getObjectFactory() );
	}
];
