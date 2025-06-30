<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower;

use StringUtils;

final class ListUtils {

	/**
	 * Split a list of delimited values into an array by a given delimiter.
	 * Whitespaces are trimmed from the end of each value, and empty values are filtered out.
	 *
	 * @param string $sep Delimiter used to separate the values, an empty string to make each character a value.
	 * @param string $list List to split.
	 * @return array The values, in an array of strings.
	 */
	public static function explode( string $sep, string $list ): array {
		if ( $sep === '' ) {
			$inValues = preg_split( '/(?<!^)(?!$)/u', $list );
		} else {
			$inValues = StringUtils::explode( $sep, $list );
		}

		if ( $inValues === false ) {
			return [];
		}

		$outValues = [];
		foreach ( $inValues as $value ) {
			$value = trim( $value );
			if ( $value !== '' ) {
				$outValues[] = ParserPower::unescape( $value );
			}
		}

		return $outValues;
	}

	/**
	 * Join list values into a list with a given separator.
	 *
	 * @param array $values Array with the output values.
	 * @param string $sep Delimiter used to separate the values.
	 * @param ?string $conj Delimiter used to separate the last 2 values, null to use the base delimiter.
	 * @return string The output list.
	 */
	public static function implode( array $values, string $sep, ?string $conj = null ): string {
		$list = end( $values );
		if ( key( $values ) === null ) {
			return '';
		}

		$value = prev( $values );
		if ( key( $values ) === null ) {
			return $list;
		}

		if ( $conj !== null ) {
			$list = $value . $conj . $list;
			$value = prev( $values );
		}

		while ( key( $values ) !== null ) {
			$list = $value . $sep . $list;
			$value = prev( $values );
		}

		return $list;
	}

	/**
	 * Split a list value into an array of fields by a given delimiter.
	 *
	 * @param string $sep Delimiter used to separate the fields.
	 * @param string $value Value to split.
	 * @param ?int $fieldLimit Maximum number of fields, null if there is no upper bound.
	 * @return array The fields, in an array of strings.
	 */
	public static function explodeValue( string $sep, string $value, ?int $fieldLimit = null ): array {
		if ( $sep === '' ) {
			return [ $value ];
		} else {
			return explode( $sep, $value, $fieldLimit ?? PHP_INT_MAX );
		}
	}

	/**
	 * Split a token into an array by a given delimiter.
	 * Whitespaces are trimmed from the end of each token.
	 *
	 * @param string $sep Delimiter used to separate the tokens.
	 * @param string $token Token to split.
	 * @return array The tokens, in an array of strings.
	 */
	public static function explodeToken( string $sep, string $token ): array {
		if ( $sep === '' ) {
			return [ trim( $token ) ];
		} else {
			return array_map( 'trim', explode( $sep, $token ) );
		}
	}

	/**
	 * Slice an array according to the specified 1-based offset and length.
	 *
	 * @param array $values Array to slice.
	 * @param int $offset 1-based index of the first value to extract.
	 * @param ?int $length Maximum number of elements to extract.
	 * @return array A new sliced array.
	 */
	public static function slice( array $values, int $offset = 0, ?int $length = null ): array {
		if ( $offset > 0 ) {
			$offset = $offset - 1;
		}

		// If a negative $offset is bigger than $values,
		// we need to reduce the number of values array_slice will retrieve.
		if ( $offset < 0 && $length !== null ) {
			$outOfBounds = $offset + count( $values );
			if ( $outOfBounds < 0 ) {
				$length = $length + $outOfBounds;
			}
		}

		return array_slice( $values, $offset, $length );
	}

	/**
	 * Get an element from an array from its 1-based index.
	 *
	 * @param array $values Array to get the element from.
	 * @param int $index 1-based index of the array element to get, or a negative value to start from the end.
	 * @return string The array element, or empty string if not found.
	 */
	public static function get( array $values, int $index ): string {
		if ( $index === 0 ) {
			return '';
		}

		$outValues = self::slice( $values, $index, 1 );
		return $outValues[0] ?? '';
	}

	/**
	 * Wraps the given intro and outro around the given content after replacing a given count token
	 * in the intro or outro with the given count.
	 *
	 * @param string $intro The intro text.
	 * @param string $content The inner content.
	 * @param string $outro The outro test.
	 * @param string $countToken The token to replace with count. Null or empty to skip.
	 * @param int $count The count to replace the token with.
	 * @return string The content wrapped by the intro and outro.
	 */
	public static function applyIntroAndOutro(
		string $intro,
		string $content,
		string $outro,
		string $countToken,
		int $count
	): string {
		if ( $countToken !== null && $countToken !== '' ) {
			$intro = str_replace( $countToken, (string)$count, $intro );
			$outro = str_replace( $countToken, (string)$count, $outro );
		}
		return $intro . $content . $outro;
	}
}
