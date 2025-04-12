<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Operation;

use MediaWiki\Extension\ParserPower\ParserPower;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

/**
 * List value operation that replaces tokens with the list value fields in a pattern.
 */
final class PatternOperation implements WikitextOperation {

	/**
	 * @param Parser $parser Parser object.
	 * @param PPFrame $frame Parser frame object.
	 * @param string $pattern Pattern to apply, as variable-free escaped wikitext.
	 * @param array $tokens Tokens to replace with value fields in the pattern.
	 * @param string $indexToken Token to replace with the 1-based value index, empty in not provided.
	 */
	public function __construct(
		private readonly Parser $parser,
		private readonly PPFrame $frame,
		private string $pattern = '',
		private array $tokens = [],
		private string $indexToken = ''
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function apply( array $fields, ?int $index = null ): string {
		$result = $this->pattern;
		if ( $result === '' ) {
			return $fields[0];
		}

		if ( $index !== null ) {
			$result = ParserPower::applyPattern( (string)$index, $this->indexToken, $result );
		}

		foreach ( $this->tokens as $i => $token ) {
			$result = ParserPower::applyPattern( $fields[$i] ?? '', $token, $result );
		}

		$result = ParserPower::unescape( $result );
		return ParserPower::evaluateUnescaped( $this->parser, $this->frame, $result, ParserPower::WITH_ARGS );
	}

	/**
	 * @inheritDoc
	 */
	public function getFieldLimit(): ?int {
		return count( $this->tokens );
	}
}
