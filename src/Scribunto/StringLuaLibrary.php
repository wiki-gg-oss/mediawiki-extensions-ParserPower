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
		$lib = [
			'escape' => [ $this, 'escape' ],
			'unescape' => [ $this, 'unescape' ]
		];

		return $this->getEngine()->registerInterface( __DIR__ . '/lua/ParserPower.string.lua', $lib );
	}

	/**
	 * Replace all appropriate characters with escape sequences.
	 *
	 * @param string $text The text to escape.
	 * @return array The escaped text.
	 */
	public function escape( string $text ): array {
		return [ ParserPower::escape( $text ) ];
	}

	/**
	 * Replace all escape sequences with the appropriate characters.
	 *
	 * @param string $text The text to unescape.
	 * @return array The text with all escape sequences replaced.
	 */
	public function unescape( string $text ): array {
		return [ ParserPower::unescape( $text ) ];
	}
}
