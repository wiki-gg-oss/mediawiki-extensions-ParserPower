<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Hooks;

use MediaWiki\Extension\ParserPower\Scribunto\StringLuaLibrary;
use MediaWiki\Extension\Scribunto\Hooks\ScribuntoExternalLibrariesHook;

/**
 * Hook handler for registering Lua libraries with Scribunto.
 */
final class ScribuntoHooks implements ScribuntoExternalLibrariesHook {

	/**
	 * Register mw.ext.ParserPower Lua libraries.
	 *
	 * @param string $engine
	 * @param array &$extraLibraries
	 * @return void
	 */
	public function onScribuntoExternalLibraries( string $engine, array &$extraLibraries ) {
		if ( $engine === 'lua' ) {
			$extraLibraries['mw.ext.ParserPower.string'] = StringLuaLibrary::class;
		}
	}
}
