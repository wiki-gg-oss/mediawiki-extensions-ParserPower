<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function\PageForms;

use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;
use MediaWiki\Parser\PPNode_Hash_Array;
use MediaWiki\Extension\ParserPower\Function\ParserFunction;

/**
 * Page Forms #arraymaptemplate parser function.
 */
final class ArrayMapTemplateFunction implements ParserFunction {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'arraymaptemplate';
	}

	/**
	 * @inheritDoc
	 */
	public function render( Parser $parser, PPFrame $frame, array $params ): string {
		// Set variables.
		$value = isset( $args[0] ) ? trim( $frame->expand( $args[0] ) ) : '';
		$template = isset( $args[1] ) ? trim( $frame->expand( $args[1] ) ) : '';
		$delimiter = isset( $args[2] ) ? trim( $frame->expand( $args[2] ) ) : ',';
		$new_delimiter = isset( $args[3] ) ? trim( $frame->expand( $args[3] ) ) : ', ';
		// Unstrip some.
		$delimiter = $parser->getStripState()->unstripNoWiki( $delimiter );
		// let '\n' represent newlines
		$delimiter = str_replace( '\n', "\n", $delimiter );
		$new_delimiter = str_replace( '\n', "\n", $new_delimiter );

		if ( $delimiter == '' ) {
			$values_array = preg_split( '/(.)/u', $value, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE );
		} else {
			$values_array = explode( $delimiter, $value );
		}

		$results_array = [];
		foreach ( $values_array as $old_value ) {
			$old_value = trim( $old_value );
			if ( $old_value == '' ) {
				continue;
			}
			$bracketed_value = $frame->virtualBracketedImplode( '{{', '|', '}}',
				$template, '1=' . $old_value );
			// Special handling if preprocessor class is set to
			// 'Preprocessor_Hash'.
			if ( $bracketed_value instanceof PPNode_Hash_Array ) {
				$bracketed_value = $bracketed_value->value;
			}
			$results_array[] = $parser->replaceVariables( implode( '', $bracketed_value ), $frame );
		}
		return implode( $new_delimiter, $results_array );
	}
}
