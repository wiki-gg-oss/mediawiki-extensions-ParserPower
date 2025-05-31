<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Operation;

use MediaWiki\Extension\ParserPower\ParserPower;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;
use MediaWiki\Parser\PPNode;

/**
 * List value operation that transcludes a template, passing the list value fields as parameters.
 */
final class TemplateOperation implements WikitextOperation {

	/**
	 * @param Parser $parser Parser object.
	 * @param PPFrame $frame Parser frame object.
	 * @param string $template Title of the template to transclude, as variable-free wikitext.
	 */
	public function __construct(
		private readonly Parser $parser,
		private readonly PPFrame $frame,
		private string $template = ''
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function apply( array $fields, ?int $index = null ): string {
		if ( $this->template === '' ) {
			return $fields[0] ?? '';
		}

		$result = '{{' . $this->template;
		foreach ( $fields as $i => $value ) {
			$key = is_int( $i ) ? (string)( $i + 1 ) : $i;
			if ( $value instanceof PPNode ) {
				$value = $this->frame->expand( $value, PPFrame::RECOVER_ORIG );
			}
			$result .= '|' . $key . '=' . $value;
		}
		if ( $index !== null ) {
			$result .= '|index=' . $index;
		}
		$result .= '}}';

		return ParserPower::evaluateUnescaped( $this->parser, $this->frame, $result, ParserPower::WITH_ARGS );
	}

	/**
	 * @inheritDoc
	 */
	public function getFieldLimit(): ?int {
		return null;
	}
}
