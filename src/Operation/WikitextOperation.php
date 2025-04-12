<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Operation;

/**
 * Interface for an operation on a list value.
 */
interface WikitextOperation {

	/**
	 * Applies the operation on a list value, split into fields.
	 *
	 * @param array $fields Fields the input value is made of. Must not have more values than getFieldLimit().
	 * @param ?int $index Index of the value in the list, null if not provided.
	 * @return string The operation result, as variable-free wikitext.
	 */
	public function apply( array $fields, ?int $index = null ): string;

	/**
	 * Get the maximum number of fields that an input value may contain.
	 *
	 * @return ?int The field limit, null if there is no upper bound.
	 */
	public function getFieldLimit(): ?int;
}
