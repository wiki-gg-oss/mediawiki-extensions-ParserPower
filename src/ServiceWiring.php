<?php

/** @license GPL-2.0-or-later */

use MediaWiki\Config\Config;
use MediaWiki\MediaWikiServices;

return [
	'ParserPower.Config' => static function ( MediaWikiServices $services ): Config {
		return $services->getConfigFactory()->makeConfig( 'ParserPower' );
	}
];
