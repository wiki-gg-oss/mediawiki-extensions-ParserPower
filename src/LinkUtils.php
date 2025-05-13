<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower;

final class LinkUtils {

	/**
	 * Replace links within text using a callback.
	 *
	 * @param string $text
	 * @param callable $callback Callback with link parts as arguments.
	 * @return string The text with links replaced.
	 */
	public static function replace( string $text, callable $callback ): string {
		return preg_replace_callback( '/\[\[(.*?)\]\]/', static function ( $matches ) use ( $callback ) {
			$parts = explode( '|', $matches[1], 2 );
			return $callback( $parts[0], $parts[1] ?? null );
		}, $text );
	}
}
