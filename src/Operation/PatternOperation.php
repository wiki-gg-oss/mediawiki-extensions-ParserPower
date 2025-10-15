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
	 * @param string $indexToken Token to replace with the 1-based value index, empty if not provided.
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
		if ( $this->pattern === '' ) {
			return $fields[0];
		}

		$repl = [];
		if ( $this->indexToken !== '' && $index !== null ) {
			$repl[$this->indexToken] = (string)$index;
		}
		foreach ( $this->tokens as $i => $token ) {
			if ( $token !== '' ) {
				$repl[$token] = $fields[$i] ?? '';
			}
		}

		$result = strtr( $this->pattern, $repl );
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
