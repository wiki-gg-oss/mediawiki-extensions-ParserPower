<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower;

use StringUtils;

final class ListUtils {

	/**
	 * Flag for index search returning a positive index. 0 as this is a default mode.
	 */
	public const INDEX_POS = 0;
	/**
	 * Flag for index search returning a negative index.
	 */
	public const INDEX_NEG = 4;
	/**
	 * Flag for case insensitive index search. 0 as this is a default mode.
	 */
	public const INDEX_NCS = 0;
	/**
	 * Flag for case sensitive index search.
	 */
	public const INDEX_CS = 2;
	/**
	 * Flag for forward index search. 0 as this is a default mode.
	 */
	public const INDEX_ASC = 0;
	/**
	 * Flag for reverse index search.
	 */
	public const INDEX_DESC = 1;

	/**
	 * Flags for duplicate removal in lists.
	 */
	public const DUPLICATES_KEEP = 0;
	public const DUPLICATES_STRIP = 1;
	public const DUPLICATES_PRESTRIP = 2;
	public const DUPLICATES_POSTSTRIP = 4;

	/**
	 * Flags for item sort mode in lists.
	 */
	public const SORTMODE_NONE = 0;
	public const SORTMODE_PRE = 1;
	public const SORTMODE_POST = 2;
	public const SORTMODE_COMPAT = 4;

	public const PARAM_OPTIONS = [
		'counttoken' => [ 'unescape' => true ],
		'csoption' => [],
		'default' => [ 'unescape' => true ],
		'duplicates' => [],
		'fieldsep' => [ 'unescape' => true ],
		'keep' => [],
		'keepcs' => [],
		'keepsep' => [ 'default' => ',' ],
		'index' => [ 'unescape' => true ],
		'indexoptions' => [],
		'indextoken' => [ 'unescape' => true ],
		'insep' => [ 'unescape' => true, 'default' => ',' ],
		'insep1' => [ 'unescape' => true, 'default' => ',' ],
		'insep2' => [ 'unescape' => true, 'default' => ',' ],
		'intro' => [ 'unescape' => true ],
		'length' => [ 'unescape' => true ],
		'list' => [],
		'list1' => [],
		'list2' => [],
		'outro' => [ 'unescape' => true ],
		'outsep' => [ 'unescape' => true, 'default' => ', ' ],
		'matchpattern' => [],
		'matchtemplate' => [],
		'mergepattern' => [],
		'mergetemplate' => [],
		'outconj' => [ 'unescape' => true ],
		'pattern' => [],
		'remove' => [],
		'removecs' => [],
		'removesep' => [ 'default' => ',' ],
		'removecs' => [],
		'sortmode' => [],
		'sortoptions' => [],
		'subsort' => [],
		'subsortoptions' => [],
		'template' => [],
		'token' => [ 'unescape' => true ],
		'token1' => [ 'unescape' => true ],
		'token2' => [ 'unescape' => true ],
		'tokensep' => [ 'unescape' => true, 'default' => ',' ],
		'uniquecs' => [],
		'value' => [ 'unescape' => true ]
	];

	/**
	 * This function converts a string containing a boolean keyword into a boolean.
	 *
	 * @param string $text The string containg a boolean keyword.
	 * @param bool $default Value that should be used by default.
	 * @return bool
	 */
	public static function decodeBool( string $text, bool $default = false ): bool {
		$text = strtolower( $text );
		switch ( $text ) {
			case 'yes':
				return true;
			case 'no':
				return false;
			default:
				return $default;
		}
	}

	/**
	 * This function converts a string containing a duplicate removal keyword into an integer of duplicate mode flags.
	 *
	 * @param string $text The string containing a duplicate removal keyword.
	 * @param int $default Any flags that should be set by default.
	 * @return int The flags representing the requested mode.
	 */
	public static function decodeDuplicates( string $text, int $default = 0 ): int {
		$text = strtolower( $text );
		switch ( $text ) {
			case 'keep':
				return self::DUPLICATES_KEEP;
			case 'strip':
				return self::DUPLICATES_STRIP | self::DUPLICATES_POSTSTRIP;
			case 'prestrip':
				return self::DUPLICATES_PRESTRIP;
			case 'poststrip':
				return self::DUPLICATES_POSTSTRIP;
			case 'pre/poststrip':
				return self::DUPLICATES_PRESTRIP | self::DUPLICATES_POSTSTRIP;
			default:
				return $default;
		}
	}

	/**
	 * This function converts a string containing a case sensitivity keyword into a boolean.
	 *
	 * @param string $text The string containg a case sensitivity keyword.
	 * @param bool $default Value that should be used by default.
	 * @return bool True if case sentitive, false otherwise.
	 */
	public static function decodeCSOption( string $text, bool $default = false ): bool {
		$text = strtolower( $text );
		switch ( $text ) {
			case 'cs':
				return true;
			case 'ncs':
				return false;
			default:
				return $default;
		}
	}

	/**
	 * This function converts a string containing a sort mode keyword into an integer of sort mode flags.
	 *
	 * @param string $text The string containing a sort mode keyword.
	 * @param int $default Any flags that should be set by default.
	 * @return int The flags representing the requested mode.
	 */
	public static function decodeSortMode( string $text, int $default = 0 ): int {
		$text = strtolower( $text );
		switch ( $text ) {
			case 'nosort':
				return self::SORTMODE_NONE;
			case 'sort':
				return self::SORTMODE_COMPAT;
			case 'presort':
				return self::SORTMODE_PRE;
			case 'postsort':
				return self::SORTMODE_POST;
			case 'pre/postsort':
				return self::SORTMODE_PRE | self::SORTMODE_POST;
			default:
				return $default;
		}
	}

	/**
	 * This function converts a string containing sort option keywords into an integer of sort option flags.
	 *
	 * @param string $text The string containg sort options keywords.
	 * @param int $default Any flags that should be set by default.
	 * @return int The flags representing the requested options.
	 */
	public static function decodeSortOptions( string $text, int $default = 0 ): int {
		$optionKeywords = explode( ' ', $text );
		$options = $default;
		foreach ( $optionKeywords as $optionKeyword ) {
			switch ( strtolower( trim( $optionKeyword ) ) ) {
				case 'numeric':
					$options |= ListSorter::NUMERIC;
					break;
				case 'alpha':
					$options &= ~ListSorter::NUMERIC;
					break;
				case 'cs':
					$options |= ListSorter::CASE_SENSITIVE;
					break;
				case 'ncs':
					$options &= ~ListSorter::CASE_SENSITIVE;
					break;
				case 'desc':
					$options |= ListSorter::DESCENDING;
					break;
				case 'asc':
					$options &= ~ListSorter::DESCENDING;
					break;
			}
		}

		return $options;
	}

	/**
	 * This function converts a string containing index option keywords into an integer of index option flags.
	 *
	 * @param string $text The string containg index options keywords.
	 * @param int $default Any flags that should be set by default.
	 * @return int The flags representing the requested options.
	 */
	public static function decodeIndexOptions( string $text, int $default = 0 ): int {
		$optionKeywords = explode( ' ', $text );
		$options = $default;
		foreach ( $optionKeywords as $optionKeyword ) {
			switch ( strtolower( trim( $optionKeyword ) ) ) {
				case 'neg':
					$options |= self::INDEX_NEG;
					break;
				case 'pos':
					$options &= ~self::INDEX_NEG;
					break;
				case 'cs':
					$options |= self::INDEX_CS;
					break;
				case 'ncs':
					$options &= ~self::INDEX_CS;
					break;
				case 'desc':
					$options |= self::INDEX_DESC;
					break;
				case 'asc':
					$options &= ~self::INDEX_DESC;
					break;
			}
		}

		return $options;
	}

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
