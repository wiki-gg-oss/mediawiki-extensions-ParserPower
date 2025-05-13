<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Tag;

use MediaWiki\Extension\ParserPower\ParserPower;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

/**
 * Tag for escaping a value (<esc>, <esc1>, <esc2>, ...).
 */
final class EscTag extends ParserTag {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'esc';
	}

	/**
	 * @inheritDoc
	 */
	public function getAliases(): array {
		$names = [];
		for ( $index = 1; $index < 10; $index++ ) {
			$names[] = 'esc' . $index;
		}
		return $names;
	}

	/**
	 * @inheritDoc
	 */
	public function render( ?string $text, array $attribs, Parser $parser, PPFrame $frame ): array {
		return [ ParserPower::escape( $text ), 'markerType' => 'none' ];
	}
}
