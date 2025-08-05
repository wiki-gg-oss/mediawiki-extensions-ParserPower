<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Scribunto;

use MediaWiki\Extension\ParserPower\ParserPower;
use MediaWiki\Extension\Scribunto\Engines\LuaCommon\LibraryBase;

/**
 * Lua library with string manipulation functions (mw.ext.ParserPower.string).
 */
final class StringLuaLibrary extends LibraryBase {

	/**
	 * @inheritDoc
	 */
	public function register() {
		return $this->getEngine()->registerInterface( __DIR__ . '/lua/ParserPower.string.lua', [] );
	}
}
