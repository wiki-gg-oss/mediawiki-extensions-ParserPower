<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Tag;

use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

/**
 * Parser tag.
 */
abstract class ParserTag {

	/**
	 * Get the tag name.
	 *
	 * @return string The tag name.
	 */
	abstract public function getName(): string;

	/**
	 * Gets the list of all alternative names referencing the tag.
	 *
	 * @return array The list of tag alternative names.
	 */
	public function getAliases(): array {
		return [];
	}

	/**
	 * Gets the list of all tag names.
	 *
	 * @return array The list of tag names.
	 */
	public function getNames(): array {
		return [ $this->getName(), ...$this->getAliases() ];
	}

	/**
	 * Perform the operations of the tag, based on what text and attribute values are provided.
	 *
	 * @param ?string $text Text within the tag.
	 * @param array $attribs Attributes values of the tag.
	 * @param Parser $parser Parser object.
	 * @param PPFrame $frame Parser frame object.
	 * @return string|array The tag output.
	 */
	abstract public function render( ?string $text, array $attribs, Parser $parser, PPFrame $frame ): string|array;
}
