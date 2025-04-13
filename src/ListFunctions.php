<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower;

use Countable;
use MediaWiki\Extension\ParserPower\Operation\ListInclusionOperation;
use MediaWiki\Extension\ParserPower\Operation\PatternOperation;
use MediaWiki\Extension\ParserPower\Operation\TemplateOperation;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

final class ListFunctions {
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
	private static function decodeSortOptions( string $text, int $default = 0 ): int {
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
	private static function decodeIndexOptions( string $text, int $default = 0 ): int {
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
	private static function explodeList( string $sep, string $list ): array {
		if ( $sep === '' ) {
			$inValues = preg_split( '/(.)/u', $list, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE );
		} else {
			$inValues = explode( $sep, $list );
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
	 * Slice an array according to the specified 1-based offset and length.
	 *
	 * @param array $values Array to slice.
	 * @param int $offset 1-based index of the first value to extract.
	 * @param ?int $length Maximum number of elements to extract.
	 * @return array A new sliced array.
	 */
	private static function arraySlice( array $values, int $offset = 0, ?int $length = null ): array {
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
	 * @param int $index 1-based index of the array element to get, or a negative value to start from the end.
	 * @param array $values Array to get the element from.
	 * @return string The array element, or empty string if not found.
	 */
	private static function arrayElement( array $values, int $index ): string {
		if ( $index === 0 ) {
			return '';
		}

		$outValues = self::arraySlice( $values, $index, 1 );
		return $outValues[0] ?? '';
	}

	/**
	 * This function directs the counting operation for the lstcnt function.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return string The function output.
	 */
	public function lstcntRender( Parser $parser, PPFrame $frame, array $params ): string {
		$list = ParserPower::expand( $frame, $params[0] ?? '' );

		if ( $list === '' ) {
			return '0';
		}

		$sep = ParserPower::expand( $frame, $params[1] ?? ',', ParserPower::UNESCAPE );

		$sep = $parser->getStripState()->unstripNoWiki( $sep );

		return (string)count( self::explodeList( $sep, $list ) );
	}

	/**
	 * This function directs the delimiter replacement operation for the lstsep function.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return string The function output.
	 */
	public function lstsepRender( Parser $parser, PPFrame $frame, array $params ): string {
		$inList = ParserPower::expand( $frame, $params[0] ?? '' );

		if ( $inList === '' ) {
			return '';
		}

		$inSep = ParserPower::expand( $frame, $params[1] ?? ',', ParserPower::UNESCAPE );
		$outSep = ParserPower::expand( $frame, $params[2] ?? ',\_', ParserPower::UNESCAPE );

		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );

		$values = self::explodeList( $inSep, $inList );
		return ParserPower::evaluateUnescaped( $parser, $frame, implode( $outSep, $values ) );
	}

	/**
	 * This function directs the list element retrieval operation for the lstelem function.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return string The function output along with relevant parser options.
	 */
	public function lstelemRender( Parser $parser, PPFrame $frame, array $params ): string {
		$inList = ParserPower::expand( $frame, $params[0] ?? '' );

		if ( $inList === '' ) {
			return '';
		}

		$inSep = ParserPower::expand( $frame, $params[1] ?? ',', ParserPower::UNESCAPE );
		$inIndex = ParserPower::expand( $frame, $params[2] ?? '', ParserPower::UNESCAPE );

		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );

		$index = 1;
		if ( is_numeric( $inIndex ) ) {
			$index = intval( $inIndex );
		}

		$value = self::arrayElement( self::explodeList( $inSep, $inList ), $index );

		return ParserPower::evaluateUnescaped( $parser, $frame, $value );
	}

	/**
	 * This function directs the list subdivision and delimiter replacement operation for the lstsub function.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return string The function output.
	 */
	public function lstsubRender( Parser $parser, PPFrame $frame, array $params ): string {
		$inList = ParserPower::expand( $frame, $params[0] ?? '' );

		if ( $inList === '' ) {
			return '';
		}

		$inSep = ParserPower::expand( $frame, $params[1] ?? ',', ParserPower::UNESCAPE );
		$outSep = ParserPower::expand( $frame, $params[2] ?? ',\_', ParserPower::UNESCAPE );
		$inOffset = ParserPower::expand( $frame, $params[3] ?? '', ParserPower::UNESCAPE );
		$inLength = ParserPower::expand( $frame, $params[4] ?? '', ParserPower::UNESCAPE );

		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );

		$offset = 0;
		if ( is_numeric( $inOffset ) ) {
			$offset = intval( $inOffset );
		}

		$length = null;
		if ( is_numeric( $inLength ) ) {
			$length = intval( $inLength );
		}

		$values = self::arraySlice( self::explodeList( $inSep, $inList ), $offset, $length );

		if ( count( $values ) > 0 ) {
			return ParserPower::evaluateUnescaped( $parser, $frame, implode( $outSep, $values ) );
		} else {
			return '';
		}
	}

	/**
	 * This function directs the search operation for the lstfnd function.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return string The function output.
	 */
	public function lstfndRender( Parser $parser, PPFrame $frame, array $params ): string {
		$list = ParserPower::expand( $frame, $params[1] ?? '' );

		if ( $list === '' ) {
			return '';
		}

		$item = ParserPower::expand( $frame, $params[0] ?? '', ParserPower::UNESCAPE );
		$sep = ParserPower::expand( $frame, $params[2] ?? ',', ParserPower::UNESCAPE );
		$csOption = ParserPower::expand( $frame, $params[3] ?? '' );

		$sep = $parser->getStripState()->unstripNoWiki( $sep );
		$csOption = self::decodeCSOption( $csOption );

		$values = self::explodeList( $sep, $list );
		if ( $csOption ) {
			foreach ( $values as $value ) {
				if ( $value === $item ) {
					return ParserPower::evaluateUnescaped( $parser, $frame, $value );
				}
			}
		} else {
			foreach ( $values as $value ) {
				if ( strtolower( $value ) === strtolower( $item ) ) {
					return ParserPower::evaluateUnescaped( $parser, $frame, $value );
				}
			}
		}
		return '';
	}

	/**
	 * This function directs the search operation for the lstind function.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return string The function output.
	 */
	public function lstindRender( Parser $parser, PPFrame $frame, array $params ): string {
		$list = ParserPower::expand( $frame, $params[1] ?? '' );

		if ( $list === '' ) {
			return '';
		}

		$item = ParserPower::expand( $frame, $params[0] ?? '', ParserPower::UNESCAPE );
		$sep = ParserPower::expand( $frame, $params[2] ?? ',', ParserPower::UNESCAPE );
		$inOptions = ParserPower::expand( $frame, $params[3] ?? '' );

		$sep = $parser->getStripState()->unstripNoWiki( $sep );
		$options = self::decodeIndexOptions( $inOptions );

		$values = self::explodeList( $sep, $list );
		$count = ( is_array( $values ) || $values instanceof Countable ) ? count( $values ) : 0;
		if ( $options & self::INDEX_DESC ) {
			if ( $options & self::INDEX_CS ) {
				for ( $index = $count - 1; $index > -1; --$index ) {
					if ( $values[$index] === $item ) {
						return (string)( ( $options & self::INDEX_NEG ) ? $index - $count : $index + 1 );
					}
				}
			} else {
				for ( $index = $count - 1; $index > -1; --$index ) {
					if ( strtolower( $values[$index] ) === strtolower( $item ) ) {
						return (string)( ( $options & self::INDEX_NEG ) ? $index - $count : $index + 1 );
					}
				}
			}
		} else {
			if ( $options & self::INDEX_CS ) {
				for ( $index = 0; $index < $count; ++$index ) {
					if ( $values[$index] === $item ) {
						return (string)( ( $options & self::INDEX_NEG ) ? $index - $count : $index + 1 );
					}
				}
			} else {
				for ( $index = 0; $index < $count; ++$index ) {
					if ( strtolower( $values[$index] ) === strtolower( $item ) ) {
						return (string)( ( $options & self::INDEX_NEG ) ? $index - $count : $index + 1 );
					}
				}
			}
		}
		return '';
	}

	/**
	 * This function directs the append operation for the lstapp function.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return string The function output.
	 */
	public function lstappRender( Parser $parser, PPFrame $frame, array $params ): string {
		$list = ParserPower::expand( $frame, $params[0] ?? '' );
		$value = ParserPower::expand( $frame, $params[2] ?? '', ParserPower::UNESCAPE );

		if ( $list === '' ) {
			return ParserPower::evaluateUnescaped( $parser, $frame, $value );
		}

		$sep = ParserPower::expand( $frame, $params[1] ?? ',', ParserPower::UNESCAPE );

		$sep = $parser->getStripState()->unstripNoWiki( $sep );

		$values = self::explodeList( $sep, $list );
		if ( $value !== '' ) {
			$values[] = $value;
		}
		return ParserPower::evaluateUnescaped( $parser, $frame, implode( $sep, $values ) );
	}

	/**
	 * This function directs the prepend operation for the lstprep function.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return string The function output.
	 */
	public function lstprepRender( Parser $parser, PPFrame $frame, array $params ): string {
		$value = ParserPower::expand( $frame, $params[0] ?? '', ParserPower::UNESCAPE );
		$list = ParserPower::expand( $frame, $params[2] ?? '' );

		if ( $list === '' ) {
			return ParserPower::evaluateUnescaped( $parser, $frame, $value );
		}

		$sep = ParserPower::expand( $frame, $params[1] ?? '', ParserPower::UNESCAPE );

		$sep = $parser->getStripState()->unstripNoWiki( $sep );

		$values = self::explodeList( $sep, $list );
		if ( $value !== '' ) {
			array_unshift( $values, $value );
		}
		return ParserPower::evaluateUnescaped( $parser, $frame, implode( $sep, $values ) );
	}

	/**
	 * This function directs the joining operation for the lstjoin function.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return string The function output.
	 */
	public function lstjoinRender( Parser $parser, PPFrame $frame, array $params ): string {
		$inList1 = ParserPower::expand( $frame, $params[0] ?? '' );
		$inList2 = ParserPower::expand( $frame, $params[2] ?? '' );

		if ( $inList1 === '' && $inList2 === '' ) {
			return '';
		}

		if ( $inList1 === '' ) {
			$values1 = [];
		} else {
			$inSep1 = ParserPower::expand( $frame, $params[1] ?? ',', ParserPower::UNESCAPE );
			$inSep1 = $parser->getStripState()->unstripNoWiki( $inSep1 );
			$values1 = self::explodeList( $inSep1, $inList1 );
		}

		if ( $inList2 === '' ) {
			$values2 = [];
		} else {
			$inSep2 = ParserPower::expand( $frame, $params[3] ?? ',', ParserPower::UNESCAPE );
			$inSep2 = $parser->getStripState()->unstripNoWiki( $inSep2 );
			$values2 = self::explodeList( $inSep2, $inList2 );
		}

		$outSep = ParserPower::expand( $frame, $params[4] ?? ',\_', ParserPower::UNESCAPE );

		$values = array_merge( $values1, $values2 );
		return ParserPower::evaluateUnescaped( $parser, $frame, implode( $outSep, $values ) );
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
	private static function applyIntroAndOutro(
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

	/**
	 * This function performs the filtering operation for the listfiler function when done by value inclusion.
	 *
	 * @param array $inValues Array with the input values.
	 * @param string $values The list of values to include, not yet exploded.
	 * @param string $valueSep The delimiter separating the values to include.
	 * @param bool $valueCS true to match in a case-sensitive manner, false to match in a case-insensitive manner
	 * @return array The array stripped of any values with non-unique keys.
	 */
	private static function filterListByInclusion( array $inValues, string $values, string $valueSep, bool $valueCS ): array {
		if ( $valueSep !== '' ) {
			$includeValues = self::explodeList( $valueSep, $values );
		} else {
			$includeValues = [ ParserPower::unescape( $values ) ];
		}

		$operation = new ListInclusionOperation( $includeValues, '', 'remove', $valueCS );

		$outValues = [];
		foreach ( $inValues as $inValue ) {
			$result = $operation->apply( [ $inValue ] );
			if ( strtolower( $result ) !== 'remove' ) {
				$outValues[] = $inValue;
			}
		}

		return $outValues;
	}

	/**
	 * This function performs the filtering operation for the listfiler function when done by value exclusion.
	 *
	 * @param array $inValues Array with the input values.
	 * @param string $values The list of values to exclude, not yet exploded.
	 * @param string $valueSep The delimiter separating the values to exclude.
	 * @param bool $valueCS true to match in a case-sensitive manner, false to match in a case-insensitive manner
	 * @return array The array stripped of any values with non-unique keys.
	 */
	private static function filterListByExclusion( array $inValues, string $values, string $valueSep, bool $valueCS ): array {
		if ( $valueSep !== '' ) {
			$excludeValues = self::explodeList( $valueSep, $values );
		} else {
			$excludeValues = [ ParserPower::unescape( $values ) ];
		}

		$operation = new ListInclusionOperation( $excludeValues, 'remove', '', $valueCS );

		$outValues = [];
		foreach ( $inValues as $inValue ) {
			$result = $operation->apply( [ $inValue ] );
			if ( strtolower( $result ) !== 'remove' ) {
				$outValues[] = $inValue;
			}
		}

		return $outValues;
	}

	/**
	 * This function performs the filtering operation for the listfilter function when done by pattern.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $inValues Array with the input values.
	 * @param string $fieldSep Separator between fields, if any.
	 * @param string $indexToken Replace the current 1-based index of the element. Null/empty to skip.
	 * @param string $token The token(s) in the pattern that represents where the list value should go.
	 * @param string $tokenSep The separator between tokens if used.
	 * @param string $pattern The pattern of text containing token that list values are inserted into at that token.
	 * @return array The array stripped of any values with non-unique keys.
	 */
	private static function filterFromListByPattern(
		Parser $parser,
		PPFrame $frame,
		array $inValues,
		string $fieldSep,
		string $indexToken,
		string $token,
		string $tokenSep,
		string $pattern
	): array {
		$outValues = [];
		if ( $fieldSep !== '' && $tokenSep !== '' ) {
			$tokens = array_map( 'trim', explode( $tokenSep, $token ) );
			$tokenCount = count( $tokens );
			$operation = new PatternOperation( $parser, $frame, $pattern, $tokens, $indexToken );
			foreach ( $inValues as $i => $value ) {
				if ( $value !== '' ) {
					$result = $operation->apply( explode( $fieldSep, $value, $tokenCount ), $i + 1 );
					if ( strtolower( $result ) !== 'remove' ) {
						$outValues[] = $value;
					}
				}
			}
		} else {
			$operation = new PatternOperation( $parser, $frame, $pattern, [ $token ], $indexToken );
			foreach ( $inValues as $i => $value ) {
				if ( $value !== '' ) {
					$result = $operation->apply( [ $value ], $i + 1 );
					if ( strtolower( $result ) !== 'remove' ) {
						$outValues[] = $value;
					}
				}
			}
		}

		return $outValues;
	}

	/**
	 * This function performs the filtering operation for the listfilter function when done by template.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $inValues Array with the input values.
	 * @param string $template The template to use.
	 * @param string $fieldSep Separator between fields, if any.
	 * @return array The array stripped of any values with non-unique keys.
	 */
	private static function filterFromListByTemplate(
		Parser $parser,
		PPFrame $frame,
		array $inValues,
		string $template,
		string $fieldSep
	): array {
		$operation = new TemplateOperation( $parser, $frame, $template );

		$outValues = [];
		if ( $fieldSep === '' ) {
			foreach ( $inValues as $value ) {
				$result = $operation->apply( [ $value ] );
				if ( $value !== '' && strtolower( $result ) !== 'remove' ) {
					$outValues[] = $value;
				}
			}
		} else {
			foreach ( $inValues as $value ) {
				$result = $operation->apply( explode( $fieldSep, $value ) );
				if ( $value !== '' && strtolower( $result ) !== 'remove' ) {
					$outValues[] = $value;
				}
			}
		}

		return $outValues;
	}

	/**
	 * This function renders the listfilter function, sending it to the appropriate processing function based on what
	 * parameter values are provided.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return string The function output.
	 */
	public function listfilterRender( Parser $parser, PPFrame $frame, array $params ): string {
		$params = ParserPower::arrangeParams( $frame, $params );

		$inList = ParserPower::expand( $frame, $params["list"] ?? '' );
		$default = ParserPower::expand( $frame, $params["default"] ?? '', ParserPower::UNESCAPE );

		$keepValues = ParserPower::expand( $frame, $params["keep"] ?? '' );
		$keepSep = ParserPower::expand( $frame, $params["keepsep"] ?? ',' );
		$keepCS = ParserPower::expand( $frame, $params["keepcs"] ?? '' );
		$removeValues = ParserPower::expand( $frame, $params["remove"] ?? '' );
		$removeSep = ParserPower::expand( $frame, $params["removesep"] ?? ',' );
		$removeCS = ParserPower::expand( $frame, $params["removecs"] ?? '' );
		$template = ParserPower::expand( $frame, $params["template"] ?? '' );
		$inSep = ParserPower::expand( $frame, $params["insep"] ?? ',', ParserPower::UNESCAPE );
		$fieldSep = ParserPower::expand( $frame, $params["fieldsep"] ?? '', ParserPower::UNESCAPE );
		$indexToken = ParserPower::expand( $frame, $params["indextoken"] ?? '', ParserPower::UNESCAPE );
		$token = ParserPower::expand( $frame, $params["token"] ?? '', ParserPower::UNESCAPE );
		$tokenSep = ParserPower::expand( $frame, $params["tokensep"] ?? ',', ParserPower::UNESCAPE );
		$pattern = ParserPower::expand( $frame, $params["pattern"] ?? '', ParserPower::NO_VARS );
		$outSep = ParserPower::expand( $frame, $params["outsep"] ?? ',\_', ParserPower::UNESCAPE );
		$countToken = ParserPower::expand( $frame, $params["counttoken"] ?? '', ParserPower::UNESCAPE );
		$intro = ParserPower::expand( $frame, $params["intro"] ?? '', ParserPower::UNESCAPE );
		$outro = ParserPower::expand( $frame, $params["outro"] ?? '', ParserPower::UNESCAPE );

		if ( $inList === '' ) {
			return ParserPower::evaluateUnescaped( $parser, $frame, $default );
		}

		$keepCS = self::decodeBool( $keepCS );
		$removeCS = self::decodeBool( $removeCS );

		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );
		$tokenSep = $parser->getStripState()->unstripNoWiki( $tokenSep );

		$inValues = self::explodeList( $inSep, $inList );

		if ( $keepValues !== '' ) {
			$outValues = self::filterListByInclusion( $inValues, $keepValues, $keepSep, $keepCS );
		} elseif ( $removeValues !== '' ) {
			$outValues = self::filterListByExclusion( $inValues, $removeValues, $removeSep, $removeCS );
		} elseif ( $template !== '' ) {
			$outValues = self::filterFromListByTemplate( $parser, $frame, $inValues, $template, $fieldSep );
		} else {
			$outValues = self::filterFromListByPattern(
				$parser,
				$frame,
				$inValues,
				$fieldSep,
				$indexToken,
				$token,
				$tokenSep,
				$pattern
			);
		}

		if ( count( $outValues ) === 0 ) {
			return ParserPower::evaluateUnescaped( $parser, $frame, $default );
		}

		$count = count( $outValues );
		$outList = self::applyIntroAndOutro( $intro, implode( $outSep, $outValues ), $outro, $countToken, $count );

		return ParserPower::evaluateUnescaped( $parser, $frame, $outList );
	}

	/**
	 * This function renders the lstfltr function.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return string The function output.
	 */
	public function lstfltrRender( Parser $parser, PPFrame $frame, array $params ): string {
		$inList = ParserPower::expand( $frame, $params[2] ?? '' );

		if ( $inList === '' ) {
			return '';
		}

		$values = ParserPower::expand( $frame, $params[0] ?? '' );
		$valueSep = ParserPower::expand( $frame, $params[1] ?? ',', ParserPower::UNESCAPE );
		$inSep = ParserPower::expand( $frame, $params[3] ?? ',', ParserPower::UNESCAPE );
		$outSep = ParserPower::expand( $frame, $params[4] ?? ',\_', ParserPower::UNESCAPE );
		$csOption = ParserPower::expand( $frame, $params[5] ?? '' );

		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );
		$csOption = self::decodeCSOption( $csOption );

		$inValues = self::explodeList( $inSep, $inList );

		$outValues = self::filterListByInclusion( $inValues, $values, $valueSep, $csOption );

		if ( count( $outValues ) > 0 ) {
			return ParserPower::evaluateUnescaped( $parser, $frame, implode( $outSep, $outValues ) );
		} else {
			return '';
		}
	}

	/**
	 * This function renders the lstrm function.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return string The function output.
	 */
	public function lstrmRender( Parser $parser, PPFrame $frame, array $params ): string {
		$inList = ParserPower::expand( $frame, $params[1] ?? '' );

		if ( $inList === '' ) {
			return '';
		}

		$value = ParserPower::expand( $frame, $params[0] ?? '' );
		$inSep = ParserPower::expand( $frame, $params[2] ?? ',', ParserPower::UNESCAPE );
		$outSep = ParserPower::expand( $frame, $params[3] ?? ',\_', ParserPower::UNESCAPE );
		$csOption = ParserPower::expand( $frame, $params[4] ?? '' );

		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );
		$csOption = self::decodeCSOption( $csOption );

		$inValues = self::explodeList( $inSep, $inList );

		$outValues = self::filterListByExclusion( $inValues, $value, '', $csOption );

		if ( count( $outValues ) > 0 ) {
			return ParserPower::evaluateUnescaped( $parser, $frame, implode( $outSep, $outValues ) );
		} else {
			return '';
		}
	}

	/**
	 * This function reduces an array to unique values.
	 *
	 * @param array $values The array of values to reduce to unique values.
	 * @param bool $valueCS true to determine uniqueness case-sensitively, false to determine it case-insensitively
	 * @return array The function output.
	 */
	private static function reduceToUniqueValues( array $values, bool $valueCS ): array {
		if ( $valueCS ) {
			return array_unique( $values );
		} else {
			return array_intersect_key( $values, array_unique( array_map( 'strtolower', $values ) ) );
		}
	}

	/**
	 * This function directs the counting operation for the lstcntuniq function.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return string The function output.
	 */
	public function lstcntuniqRender( Parser $parser, PPFrame $frame, array $params ): string {
		$inList = ParserPower::expand( $frame, $params[0] ?? '' );

		if ( $inList === '' ) {
			return '0';
		}

		$sep = ParserPower::expand( $frame, $params[1] ?? ',', ParserPower::UNESCAPE );
		$csOption = ParserPower::expand( $frame, $params[2] ?? '' );

		$sep = $parser->getStripState()->unstripNoWiki( $sep );
		$csOption = self::decodeCSOption( $csOption );

		$values = self::explodeList( $sep, $inList );
		$values = self::reduceToUniqueValues( $values, $csOption );
		return (string)count( $values );
	}

	/**
	 * Generates keys by replacing tokens in a pattern with the fields in the values, excludes any value that generates
	 * any key generated by the previous values, and returns an array of the nonexcluded values.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $inValues The input list.
	 * @param string $fieldSep Separator between fields, if any.
	 * @param string $indexToken Replace the current 1-based index of the element. Null/empty to skip.
	 * @param string $token The token in the pattern that represents where the list value should go.
	 * @param ?array $tokens Or if there are mulitple fields, the tokens representing where they go.
	 * @param string $pattern The pattern of text containing token that list values are inserted into at that token.
	 * @return array An array with only values that generated unique keys via the given pattern.
	 */
	private static function reduceToUniqueValuesByKeyPattern(
		Parser $parser,
		PPFrame $frame,
		array $inValues,
		string $fieldSep,
		string $indexToken,
		string $token,
		?array $tokens,
		string $pattern
	): array {
		$previousKeys = [];
		$outValues = [];
		if ( ( isset( $tokens ) && is_array( $tokens ) ) ) {
			$tokenCount = count( $tokens );
			$operation = new PatternOperation( $parser, $frame, $pattern, $tokens, $indexToken );
			foreach ( $inValues as $i => $value ) {
				if ( $value !== '' ) {
					$key = $operation->apply( explode( $fieldSep, $value, $tokenCount ), $i + 1 );
					if ( !in_array( $key, $previousKeys ) ) {
						$previousKeys[] = $key;
						$outValues[] = $value;
					}
				}
			}
		} else {
			$operation = new PatternOperation( $parser, $frame, $pattern, [ $token ], $indexToken );
			foreach ( $inValues as $i => $value ) {
				if ( $value !== '' ) {
					$key = $operation->apply( [ $value ], $i + 1 );
					if ( !in_array( $key, $previousKeys ) ) {
						$previousKeys[] = $key;
						$outValues[] = $value;
					}
				}
			}
		}

		return $outValues;
	}

	/**
	 * Generates keys by turning the input value into one or more template parameters and processing that template,
	 * excludes any value that generates any key generated by the previous values, and returns an array of the
	 * nonexcluded values.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $inValues The input list.
	 * @param string $template
	 * @param string $fieldSep Separator between fields, if any.
	 * @return array An array with only values that generated unique keys via the given pattern.
	 */
	private static function reduceToUniqueValuesByKeyTemplate(
		Parser $parser,
		PPFrame $frame,
		array $inValues,
		string $template,
		string $fieldSep
	): array {
		$operation = new TemplateOperation( $parser, $frame, $template );

		$previousKeys = [];
		$outValues = [];
		if ( $fieldSep === '' ) {
			foreach ( $inValues as $value ) {
				$key = $operation->apply( [ $value ] );
				if ( !in_array( $key, $previousKeys ) ) {
					$previousKeys[] = $key;
					$outValues[] = $value;
				}
			}
		} else {
			foreach ( $inValues as $value ) {
				$key = $operation->apply( explode( $fieldSep, $value ) );
				if ( !in_array( $key, $previousKeys ) ) {
					$previousKeys[] = $key;
					$outValues[] = $value;
				}
			}
		}

		return $outValues;
	}

	/**
	 * This function renders the listunique function, sending it to the appropriate processing function based on what
	 * parameter values are provided.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return string The function output.
	 */
	public function listuniqueRender( Parser $parser, PPFrame $frame, array $params ): string {
		$params = ParserPower::arrangeParams( $frame, $params );

		$inList = ParserPower::expand( $frame, $params["list"] ?? '' );
		$default = ParserPower::expand( $frame, $params["default"] ?? '', ParserPower::UNESCAPE );

		$uniqueCS = ParserPower::expand( $frame, $params["uniquecs"] ?? '' );
		$template = ParserPower::expand( $frame, $params["template"] ?? '' );
		$inSep = ParserPower::expand( $frame, $params["insep"] ?? ',', ParserPower::UNESCAPE );
		$fieldSep = ParserPower::expand( $frame, $params["fieldsep"] ?? '', ParserPower::UNESCAPE );
		$indexToken = ParserPower::expand( $frame, $params["indextoken"] ?? '', ParserPower::UNESCAPE );
		$token = ParserPower::expand( $frame, $params["token"] ?? '', ParserPower::UNESCAPE );
		$tokenSep = ParserPower::expand( $frame, $params["tokensep"] ?? ',', ParserPower::UNESCAPE );
		$pattern = ParserPower::expand( $frame, $params["pattern"] ?? '' );
		$outSep = ParserPower::expand( $frame, $params["outsep"] ?? ',\_', ParserPower::UNESCAPE );
		$countToken = ParserPower::expand( $frame, $params["counttoken"] ?? '', ParserPower::UNESCAPE );
		$intro = ParserPower::expand( $frame, $params["intro"] ?? '', ParserPower::UNESCAPE );
		$outro = ParserPower::expand( $frame, $params["outro"] ?? '', ParserPower::UNESCAPE );

		if ( $inList === '' ) {
			return ParserPower::evaluateUnescaped( $parser, $frame, $default );
		}

		$uniqueCS = self::decodeBool( $uniqueCS );

		$inValues = self::explodeList( $inSep, $inList );

		if ( $fieldSep !== '' && $tokenSep !== '' ) {
			$tokens = array_map( 'trim', explode( $tokenSep, $token ) );
		}

		if ( $template !== '' ) {
			$outValues = self::reduceToUniqueValuesByKeyTemplate( $parser, $frame, $inValues, $template, $fieldSep );
		} elseif ( ( $indexToken !== '' || $token !== '' ) && $pattern !== '' ) {
			$outValues = self::reduceToUniqueValuesByKeyPattern(
				$parser,
				$frame,
				$inValues,
				$fieldSep,
				$indexToken,
				$token,
				$tokens ?? null,
				$pattern
			);
		} else {
			$outValues = self::reduceToUniqueValues( $inValues, $uniqueCS );
		}
		$count = count( $outValues );
		$outList = self::applyIntroAndOutro( $intro, implode( $outSep, $outValues ), $outro, $countToken, $count );
		return ParserPower::evaluateUnescaped( $parser, $frame, $outList );
	}

	/**
	 * This function directs the duplicate removal function for the lstuniq function.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return string The function output.
	 */
	public function lstuniqRender( Parser $parser, PPFrame $frame, array $params ): string {
		$inList = ParserPower::expand( $frame, $params[0] ?? '' );

		if ( $inList === '' ) {
			return '';
		}

		$inSep = ParserPower::expand( $frame, $params[1] ?? ',', ParserPower::UNESCAPE );
		$outSep = ParserPower::expand( $frame, $params[2] ?? ',\_', ParserPower::UNESCAPE );
		$csOption = ParserPower::expand( $frame, $params[3] ?? '' );

		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );
		$csOption = self::decodeCSOption( $csOption );

		$values = self::explodeList( $inSep, $inList );
		$values = self::reduceToUniqueValues( $values, $csOption );
		return ParserPower::evaluateUnescaped( $parser, $frame, implode( $outSep, $values ) );
	}

	/**
	 * Generates the sort keys by replacing tokens in a pattern with the fields in the values. This returns an array
	 * of the values where each element is an array with the sort key in element 0 and the value in element 1.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $values The input list.
	 * @param string $fieldSep Separator between fields, if any.
	 * @param string $indexToken
	 * @param string $token The token in the pattern that represents where the list value should go.
	 * @param ?array $tokens Or if there are mulitple fields, the tokens representing where they go.
	 * @param string $pattern The pattern of text containing token that list values are inserted into at that token.
	 * @return array An array where each value has been paired with a sort key in a two-element array.
	 */
	private static function generateSortKeysByPattern(
		Parser $parser,
		PPFrame $frame,
		array $values,
		string $fieldSep,
		string $indexToken,
		string $token,
		?array $tokens,
		string $pattern
	): array {
		$pairedValues = [];
		if ( ( isset( $tokens ) && is_array( $tokens ) ) ) {
			$tokenCount = count( $tokens );
			$operation = new PatternOperation( $parser, $frame, $pattern, $tokens, $indexToken );
			foreach ( $values as $i => $value ) {
				if ( $value !== '' ) {
					$key = $operation->apply( explode( $fieldSep, $value, $tokenCount ), $i + 1 );
					$pairedValues[] = [ $key, $value ];
				}
			}
		} else {
			$operation = new PatternOperation( $parser, $frame, $pattern, [ $token ], $indexToken );
			foreach ( $values as $i => $value ) {
				if ( $value !== '' ) {
					$key = $operation->apply( [ $value ], $i + 1 );
					$pairedValues[] = [ $key, $value ];
				}
			}
		}

		return $pairedValues;
	}

	/**
	 * Generates the sort keys by turning the input value into one or more template parameters and processing that
	 * template. This returns an array of the values where each element is an array with the sort key in element 0 and
	 * the value in element 1.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $values The input list.
	 * @param string $template
	 * @param string $fieldSep Separator between fields, if any.
	 * @return array An array where each value has been paired with a sort key in a two-element array.
	 */
	private static function generateSortKeysByTemplate(
		Parser $parser,
		PPFrame $frame,
		array $values,
		string $template,
		string $fieldSep
	): array {
		$operation = new TemplateOperation( $parser, $frame, $template );

		$pairedValues = [];
		if ( $fieldSep === '' ) {
			foreach ( $values as $value ) {
				$pairedValues[] = [ $operation->apply( [ $value ] ), $value ];
			}
		} else {
			foreach ( $values as $value ) {
				$pairedValues[] = [ $operation->apply( explode( $fieldSep, $value ) ), $value ];
			}
		}

		return $pairedValues;
	}

	/**
	 * This takes an array where each element is an array with a sort key in element 0 and a value in element 1, and it
	 * returns an array with just the values.
	 *
	 * @param array $pairedValues An array with values paired with sort keys.
	 * @return array An array with just the values.
	 */
	private static function discardSortKeys( array $pairedValues ): array {
		$values = [];

		foreach ( $pairedValues as $pairedValue ) {
			$values[] = $pairedValue[1];
		}

		return $values;
	}

	/**
	 * This takes an array where each element is an array with a sort key in element 0 and a value in element 1, and it
	 * returns an array with just the sort keys wrapped in <nowiki> tags. Used for debugging purposes.
	 *
	 * @param array $pairedValues An array with values paired with sort keys.
	 * @return array An array with just the sort keys wrapped in <nowiki>..
	 */
	private static function discardValues( array $pairedValues ): array {
		$values = [];

		foreach ( $pairedValues as $pairedValue ) {
			$values[] = '<nowiki>' . $pairedValue[0] . '</nowiki>';
		}

		return $values;
	}

	/**
	 * Sorts a list by keys
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param ListSorter $sorter The list sorter.
	 * @param array $values The input list.
	 * @param string $template The template to use.
	 * @param string $fieldSep The delimiter separating values in the input list.
	 * @param string $indexToken Replace the current 1-based index of the element. Null/empty to skip.
	 * @param string $token The token in the pattern that represents where the list value should go.
	 * @param ?array $tokens Or if there are mulitple fields, the tokens representing where they go.
	 * @param string $pattern The pattern containing token that list values are inserted into at that token.
	 * @return array An array where each value has been paired with a sort key in a two-element array.
	 */
	private static function sortListByKeys(
		Parser $parser,
		PPFrame $frame,
		ListSorter $sorter,
		array $values,
		string $template,
		string $fieldSep,
		string $indexToken,
		string $token,
		?array $tokens,
		string $pattern
	): array {
		if ( $template !== '' ) {
			$pairedValues = self::generateSortKeysByTemplate( $parser, $frame, $values, $template, $fieldSep );
		} else {
			$pairedValues = self::generateSortKeysByPattern(
				$parser,
				$frame,
				$values,
				$fieldSep,
				$indexToken,
				$token,
				$tokens,
				$pattern
			);
		}

		$sorter->sortPairs( $pairedValues );

		return self::discardSortKeys( $pairedValues );
	}

	/**
	 * This function directs the sort operation for the listsort function.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return string The function output.
	 */
	public function listsortRender( Parser $parser, PPFrame $frame, array $params ): string {
		$params = ParserPower::arrangeParams( $frame, $params );

		$inList = ParserPower::expand( $frame, $params["list"] ?? '' );
		$default = ParserPower::expand( $frame, $params["default"] ?? '', ParserPower::UNESCAPE );

		$template = ParserPower::expand( $frame, $params["template"] ?? '' );
		$inSep = ParserPower::expand( $frame, $params["insep"] ?? ',', ParserPower::UNESCAPE );
		$fieldSep = ParserPower::expand( $frame, $params["fieldsep"] ?? '', ParserPower::UNESCAPE );
		$indexToken = ParserPower::expand( $frame, $params["indextoken"] ?? '', ParserPower::UNESCAPE );
		$token = ParserPower::expand( $frame, $params["token"] ?? '', ParserPower::UNESCAPE );
		$tokenSep = ParserPower::expand( $frame, $params["tokensep"] ?? ',', ParserPower::UNESCAPE );
		$pattern = ParserPower::expand( $frame, $params["pattern"] ?? '' );
		$outSep = ParserPower::expand( $frame, $params["outsep"] ?? ',\_', ParserPower::UNESCAPE );
		$sortOptions = ParserPower::expand( $frame, $params["sortoptions"] ?? '' );
		$subsort = ParserPower::expand( $frame, $params["subsort"] ?? '' );
		$subsortOptions = ParserPower::expand( $frame, $params["subsortoptions"] ?? '' );
		$duplicates = ParserPower::expand( $frame, $params["duplicates"] ?? '' );
		$countToken = ParserPower::expand( $frame, $params["counttoken"] ?? '', ParserPower::UNESCAPE );
		$intro = ParserPower::expand( $frame, $params["intro"] ?? '', ParserPower::UNESCAPE );
		$outro = ParserPower::expand( $frame, $params["outro"] ?? '', ParserPower::UNESCAPE );

		if ( $inList === '' ) {
			return ParserPower::evaluateUnescaped( $parser, $frame, $default );
		}

		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );

		$subsort = self::decodeBool( $subsort );
		if ( $subsort ) {
			$subsortOptions = self::decodeSortOptions( $subsortOptions );
		} else {
			$subsortOptions = null;
		}

		$duplicates = self::decodeDuplicates( $duplicates );

		$values = self::explodeList( $inSep, $inList );
		if ( $duplicates & self::DUPLICATES_STRIP ) {
			$values = array_unique( $values );
		}

		if ( $fieldSep !== '' && $tokenSep !== '' ) {
			$tokens = array_map( 'trim', explode( $tokenSep, $token ) );
		}

		if ( $template !== '' || ( ( $indexToken !== '' || $token !== '' ) && $pattern !== '' ) ) {
			$sortOptions = self::decodeSortOptions( $sortOptions, ListSorter::NUMERIC );
			$sorter = new ListSorter( $sortOptions, $subsortOptions );
			$values = self::sortListByKeys(
				$parser,
				$frame,
				$sorter,
				$values,
				$template,
				$fieldSep,
				$indexToken,
				$token,
				$tokens ?? null,
				$pattern
			);
		} else {
			$sortOptions = self::decodeSortOptions( $sortOptions );
			$sorter = new ListSorter( $sortOptions );
			$values = $sorter->sort( $values );
		}

		if ( count( $values ) === 0 ) {
			return ParserPower::evaluateUnescaped( $parser, $frame, $default );
		}

		$count = count( $values );
		$outList = self::applyIntroAndOutro( $intro, implode( $outSep, $values ), $outro, $countToken, $count );
		return ParserPower::evaluateUnescaped( $parser, $frame, $outList );
	}

	/**
	 * This function directs the sort option for the lstsrt function.
	 *
	 * @param Parser $parser The parser object. Ignored.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return string The function output.
	 */
	public function lstsrtRender( Parser $parser, PPFrame $frame, array $params ): string {
		$inList = ParserPower::expand( $frame, $params[0] ?? '' );

		if ( $inList === '' ) {
			return '';
		}

		$inSep = ParserPower::expand( $frame, $params[1] ?? ',', ParserPower::UNESCAPE );
		$outSep = ParserPower::expand( $frame, $params[2] ?? ',\_', ParserPower::UNESCAPE );
		$sortOptions = ParserPower::expand( $frame, $params[3] ?? '' );

		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );
		$sortOptions = self::decodeSortOptions( $sortOptions );
		$sorter = new ListSorter( $sortOptions );

		$values = self::explodeList( $inSep, $inList );
		$values = $sorter->sort( $values );
		return ParserPower::evaluateUnescaped( $parser, $frame, implode( $outSep, $values ) );
	}

	/**
	 * This function performs the pattern changing operation for the listmap function.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param string $inList The input list.
	 * @param string $inSep The delimiter seoarating values in the input list.
	 * @param string $fieldSep The optional delimiter seoarating fields in each value.
	 * @param string $indexToken Replace the current 1-based index of the element. Null/empty to skip.
	 * @param string $token The token(s) in the pattern that represents where the list value should go.
	 * @param string $tokenSep The separator between tokens if used.
	 * @param string $pattern The pattern containing token that list values are inserted into at that token.
	 * @param string $outSep The delimiter that should separate values in the output list.
	 * @param int $sortMode What sort mode to use, if any.
	 * @param int $sortOptions Options for the sort as handled by #listsort.
	 * @param int $duplicates When to strip duplicate values, if at all.
	 * @param string $countToken The token to replace with the list count. Null/empty to skip.
	 * @param string $intro Content to include before outputted list values, only if at least one item is output.
	 * @param string $outro Content to include after outputted list values, only if at least one item is output.
	 * @param string $default Content to output if no list values are.
	 * @return string The function output.
	 */
	private static function applyPatternToList(
		Parser $parser,
		PPFrame $frame,
		string $inList,
		string $inSep,
		string $fieldSep,
		string $indexToken,
		string $token,
		string $tokenSep,
		string $pattern,
		string $outSep,
		int $sortMode,
		int $sortOptions,
		int $duplicates,
		string $countToken,
		string $intro,
		string $outro,
		string $default
	): string {
		if ( $inList === '' ) {
			return ParserPower::evaluateUnescaped( $parser, $frame, $default );
		}

		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );

		$sorter = new ListSorter( $sortOptions );

		$inValues = self::explodeList( $inSep, $inList );

		if ( $duplicates & self::DUPLICATES_PRESTRIP ) {
			$inValues = array_unique( $inValues );
		}

		if ( ( $indexToken !== '' && $sortMode & self::SORTMODE_COMPAT ) || $sortMode & self::SORTMODE_PRE ) {
			$inValues = $sorter->sort( $inValues );
		}

		$outValues = [];
		if ( $fieldSep !== '' && $tokenSep !== '' ) {
			$tokens = array_map( 'trim', explode( $tokenSep, $token ) );
			$tokenCount = count( $tokens );
			$operation = new PatternOperation( $parser, $frame, $pattern, $tokens, $indexToken );
			foreach ( $inValues as $i => $inValue ) {
				if ( $inValue !== '' ) {
					$outValue = $operation->apply( explode( $fieldSep, $inValue, $tokenCount ), $i + 1 );
					if ( $outValue !== '' ) {
						$outValues[] = $outValue;
					}
				}
			}
		} else {
			$operation = new PatternOperation( $parser, $frame, $pattern, [ $token ], $indexToken );
			foreach ( $inValues as $i => $inValue ) {
				if ( $inValue !== '' ) {
					$outValue = $operation->apply( [ $inValue ], $i + 1 );
					if ( $outValue !== '' ) {
						$outValues[] = $outValue;
					}
				}
			}
		}

		if ( $duplicates & self::DUPLICATES_POSTSTRIP ) {
			$outValues = array_unique( $outValues );
		}

		if ( ( $indexToken === '' && $sortMode & self::SORTMODE_COMPAT ) || $sortMode & self::SORTMODE_POST ) {
			$outValues = $sorter->sort( $outValues );
		}

		if ( count( $outValues ) === 0 ) {
			return ParserPower::evaluateUnescaped( $parser, $frame, $default );
		}

		if ( $countToken !== null && $countToken !== '' ) {
			$intro = str_replace( $countToken, (string)count( $outValues ), $intro );
			$outro = str_replace( $countToken, (string)count( $outValues ), $outro );
		}
		return ParserPower::evaluateUnescaped( $parser, $frame, $intro . implode( $outSep, $outValues ) . $outro );
	}

	/**
	 * This function performs the sort option for the listmtemp function.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param string $inList The input list.
	 * @param string $template The template to use.
	 * @param string $inSep The delimiter seoarating values in the input list.
	 * @param string $fieldSep The optional delimiter seoarating fields in each value.
	 * @param string $outSep The delimiter that should separate values in the output list.
	 * @param int $sortMode What sort mode to use, if any.
	 * @param int $sortOptions Options for the sort as handled by #listsort.
	 * @param int $duplicates When to strip duplicate values, if at all.
	 * @param string $countToken The token to replace with the list count. Null/empty to skip.
	 * @param string $intro Content to include before outputted list values, only if at least one item is output.
	 * @param string $outro Content to include after outputted list values, only if at least one item is output.
	 * @param string $default Content to output if no list values are.
	 * @return string The function output.
	 */
	private static function applyTemplateToList(
		Parser $parser,
		PPFrame $frame,
		string $inList,
		string $template,
		string $inSep,
		string $fieldSep,
		string $outSep,
		int $sortMode,
		int $sortOptions,
		int $duplicates,
		string $countToken,
		string $intro,
		string $outro,
		string $default
	): string {
		if ( $inList === '' ) {
			return ParserPower::evaluateUnescaped( $parser, $frame, $default );
		}

		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );

		$sorter = new ListSorter( $sortOptions );

		$inValues = self::explodeList( $inSep, $inList );
		if ( $duplicates & self::DUPLICATES_PRESTRIP ) {
			$inValues = array_unique( $inValues );
		}

		if ( $sortMode & self::SORTMODE_PRE ) {
			$inValues = $sorter->sort( $inValues );
		}

		$operation = new TemplateOperation( $parser, $frame, $template );

		$outValues = [];
		if ( $fieldSep === '' ) {
			foreach ( $inValues as $inValue ) {
				$outValues[] = $operation->apply( [ $inValue ] );
			}
		} else {
			foreach ( $inValues as $inValue ) {
				$outValues[] = $operation->apply( explode( $fieldSep, $inValue ) );
			}
		}

		if ( $sortMode & ( self::SORTMODE_POST | self::SORTMODE_COMPAT ) ) {
			$outValues = $sorter->sort( $outValues );
		}

		if ( $duplicates & self::DUPLICATES_POSTSTRIP ) {
			$outValues = array_unique( $outValues );
		}

		if ( count( $outValues ) === 0 ) {
			return ParserPower::evaluateUnescaped( $parser, $frame, $default );
		}

		$count = count( $outValues );
		$outList = self::applyIntroAndOutro( $intro, implode( $outSep, $outValues ), $outro, $countToken, $count );
		return ParserPower::evaluateUnescaped( $parser, $frame, $outList );
	}

	/**
	 * This function renders the listmap function, sending it to the appropriate processing function based on what
	 * parameter values are provided.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return string The function output.
	 */
	public function listmapRender( Parser $parser, PPFrame $frame, array $params ): string {
		$params = ParserPower::arrangeParams( $frame, $params );

		$inList = ParserPower::expand( $frame, $params["list"] ?? '' );
		$default = ParserPower::expand( $frame, $params["default"] ?? '', ParserPower::UNESCAPE );

		$template = ParserPower::expand( $frame, $params["template"] ?? '' );
		$inSep = ParserPower::expand( $frame, $params["insep"] ?? ',', ParserPower::UNESCAPE );
		$fieldSep = ParserPower::expand( $frame, $params["fieldsep"] ?? '', ParserPower::UNESCAPE );
		$indexToken = ParserPower::expand( $frame, $params["indextoken"] ?? '', ParserPower::UNESCAPE );
		$token = ParserPower::expand( $frame, $params["token"] ?? '', ParserPower::UNESCAPE );
		$tokenSep = ParserPower::expand( $frame, $params["tokensep"] ?? ',', ParserPower::UNESCAPE );
		$pattern = ParserPower::expand( $frame, $params["pattern"] ?? '' );
		$outSep = ParserPower::expand( $frame, $params["outsep"] ?? ',\_', ParserPower::UNESCAPE );
		$sortMode = ParserPower::expand( $frame, $params["sortmode"] ?? '' );
		$sortOptions = ParserPower::expand( $frame, $params["sortoptions"] ?? '' );
		$duplicates = ParserPower::expand( $frame, $params["duplicates"] ?? '' );
		$countToken = ParserPower::expand( $frame, $params["counttoken"] ?? '', ParserPower::UNESCAPE );
		$intro = ParserPower::expand( $frame, $params["intro"] ?? '', ParserPower::UNESCAPE );
		$outro = ParserPower::expand( $frame, $params["outro"] ?? '', ParserPower::UNESCAPE );

		if ( $inList === '' ) {
			return ParserPower::evaluateUnescaped( $parser, $frame, $default );
		}

		$sortMode = self::decodeSortMode( $sortMode );
		$sortOptions = self::decodeSortOptions( $sortOptions );
		$duplicates = self::decodeDuplicates( $duplicates );

		if ( $template !== '' ) {
			return self::applyTemplateToList(
				$parser,
				$frame,
				$inList,
				$template,
				$inSep,
				$fieldSep,
				$outSep,
				$sortMode,
				$sortOptions,
				$duplicates,
				$countToken,
				$intro,
				$outro,
				$default
			);
		} else {
			return self::applyPatternToList(
				$parser,
				$frame,
				$inList,
				$inSep,
				$fieldSep,
				$indexToken,
				$token,
				$tokenSep,
				$pattern,
				$outSep,
				$sortMode,
				$sortOptions,
				$duplicates,
				$countToken,
				$intro,
				$outro,
				$default
			);
		}
	}

	/**
	 * This function performs the sort option for the listm function.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return string The function output.
	 */
	public function lstmapRender( Parser $parser, PPFrame $frame, array $params ): string {
		$inList = ParserPower::expand( $frame, $params[0] ?? '' );

		if ( $inList === '' ) {
			return '';
		}

		$inSep = ParserPower::expand( $frame, $params[1] ?? ',', ParserPower::UNESCAPE );
		$token = ParserPower::expand( $frame, $params[2] ?? 'x', ParserPower::UNESCAPE );
		$pattern = ParserPower::expand( $frame, $params[3] ?? 'x' );
		$outSep = ParserPower::expand( $frame, $params[4] ?? ',\_', ParserPower::UNESCAPE );
		$sortMode = ParserPower::expand( $frame, $params[5] ?? '' );
		$sortOptions = ParserPower::expand( $frame, $params[6] ?? '' );

		$sortMode = self::decodeSortMode( $sortMode );
		$sortOptions = self::decodeSortOptions( $sortOptions );

		return self::applyPatternToList(
			$parser,
			$frame,
			$inList,
			$inSep,
			'',
			'',
			$token,
			'',
			$pattern,
			$outSep,
			$sortMode,
			$sortOptions,
			0,
			'',
			'',
			'',
			''
		);
	}

	/**
	 * This function performs the sort option for the lstmaptemp function.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return string The function output.
	 */
	public function lstmaptempRender( Parser $parser, PPFrame $frame, array $params ): string {
		$inList = ParserPower::expand( $frame, $params[0] ?? '' );

		if ( $inList === '' ) {
			return '';
		}

		$template = ParserPower::expand( $frame, $params[1] ?? '' );
		$inSep = ParserPower::expand( $frame, $params[2] ?? ',', ParserPower::UNESCAPE );
		$outSep = ParserPower::expand( $frame, $params[3] ?? ',\_', ParserPower::UNESCAPE );
		$sortMode = ParserPower::expand( $frame, $params[4] ?? '' );
		$sortOptions = ParserPower::expand( $frame, $params[5] ?? '' );

		$sortMode = self::decodeSortMode( $sortMode );
		$sortOptions = self::decodeSortOptions( $sortOptions );

		if ( $template === '' ) {
			return self::applyPatternToList(
				$parser,
				$frame,
				$inList,
				$inSep,
				'',
				'',
				'',
				'',
				'',
				$outSep,
				$sortMode,
				$sortOptions,
				0,
				'',
				'',
				'',
				''
			);
		} else {
			return self::applyTemplateToList(
				$parser,
				$frame,
				$inList,
				$template,
				$inSep,
				'',
				$outSep,
				$sortMode,
				$sortOptions,
				0,
				'',
				'',
				'',
				''
			);
		}
	}

	/**
	 * Breaks the input values into fields and then replaces the indicated tokens in the pattern
	 * with those field values. This is for special cases when two sets of replacements are
	 * necessary for a given pattern.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param string $inValue1 The first value to (potentially) split and replace tokens with
	 * @param string $inValue2 The second value to (potentially) split and replace tokens with
	 * @param string $fieldSep The delimiter separating the fields in the value.
	 * @param array $tokens1 The list of tokens to replace when performing the replacement for $inValue1.
	 * @param array $tokens2 The list of tokens to replace when performing the replacement for $inValue2.
	 * @param string $pattern Pattern containing tokens to be replaced by field (or unsplit) values.
	 * @return string The result of the token replacement within the pattern.
	 */
	private static function applyTwoSetFieldPattern(
		Parser $parser,
		PPFrame $frame,
		string $inValue1,
		string $inValue2,
		string $fieldSep,
		array $tokens1,
		array $tokens2,
		string $pattern
	): string {
		$tokenCount1 = count( $tokens1 );
		$tokenCount2 = count( $tokens2 );
		$operation = new PatternOperation( $parser, $frame, $pattern, [ ...$tokens1, ...$tokens2 ] );

		if ( $inValue1 === '' || $inValue2 === '' ) {
			return '';
		}

		if ( $fieldSep === '' ) {
			$fields = [ $inValue1, $tokenCount1 => $inValue2 ];
		} else {
			$fields = explode( $fieldSep, $inValue1, $tokenCount1 );
			foreach ( explode( $fieldSep, $inValue1, $tokenCount2 ) as $i => $field ) {
				$fields[$tokenCount1 + $i] = $field;
			}
		}

		return $operation->apply( $fields );
	}

	/**
	 * Turns the input value into one or more template parameters, processes the templates with those parameters, and
	 * returns the result.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param string $inValue1 The first value to change into one or more template parameters.
	 * @param string $inValue2 The second value to change into one of more template parameters.
	 * @param string $template The template to pass the parameters to.
	 * @param string $fieldSep The delimiter separating the parameter values.
	 * @return string The result of the template.
	 */
	private static function applyTemplateToTwoValues(
		Parser $parser,
		PPFrame $frame,
		string $inValue1,
		string $inValue2,
		string $template,
		string $fieldSep
	): string {
		$operation = new TemplateOperation( $parser, $frame, $template );

		if ( $fieldSep === '' ) {
			return $operation->apply( [ $inValue1, $inValue2 ] );
		} else {
			return $operation->apply( [ ...explode( $fieldSep, $inValue1 ), ...explode( $fieldSep, $inValue2 ) ] );
		}
	}

	/**
	 * This function performs repeated merge passes until either the input array is merged to a single value, or until
	 * a merge pass is completed that does not perform any further merges (pre- and post-pass array count is the same).
	 * Each merge pass operates by performing a conditional on all possible pairings of items, immediately merging two
	 * if the conditional indicates it should and reducing the possible pairings. The logic for the conditional and
	 * the actual merge process is supplied through a user-defined function.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $values The input values, should be already exploded and fully preprocessed.
	 * @param callable $applyFunction Valid name of the function to call for both match and merge processes.
	 * @param array $matchParams Parameter values for the matching process, with open spots for the values.
	 * @param array $mergeParams Parameter values for the merging process, with open spots for the values.
	 * @param int $valueIndex1 The index in $matchParams and $mergeParams where the first value is to go.
	 * @param int $valueIndex2 The index in $matchParams and $mergeParams where the second value is to go.
	 * @return array An array with the output values.
	 */
	private static function iterativeListMerge(
		Parser $parser,
		PPFrame $frame,
		array $values,
		callable $applyFunction,
		array $matchParams,
		array $mergeParams,
		int $valueIndex1,
		int $valueIndex2
	): array {
		$checkedPairs = [];

		do {
			$preCount = $count = count( $values );

			for ( $i1 = 0; $i1 < $count; ++$i1 ) {
				$value1 = $matchParams[$valueIndex1] = $mergeParams[$valueIndex1] = $values[$i1];
				$shift = 0;

				for ( $i2 = $i1 + 1; $i2 < $count; ++$i2 ) {
					$value2 = $matchParams[$valueIndex2] = $mergeParams[$valueIndex2] = $values[$i2];
					unset( $values[$i2] );

					if ( isset( $checkedPairs[$value1][$value2] ) ) {
						$doMerge = $checkedPairs[$value1][$value2];
					} else {
						$doMerge = call_user_func_array( $applyFunction, $matchParams );
						$doMerge = self::decodeBool( $doMerge );
						$checkedPairs[$value1][$value2] = $doMerge;
					}

					if ( $doMerge ) {
						$value1 = call_user_func_array( $applyFunction, $mergeParams );
						$matchParams[$valueIndex1] = $mergeParams[$valueIndex1] = $value1;
						$shift += 1;
					} else {
						$values[$i2 - $shift] = $value2;
					}
				}

				$values[$i1] = $value1;
				$count -= $shift;
			}
		} while ( $count < $preCount && $count > 1 );

		return $values;
	}

	/**
	 * This function performs the pattern changing operation for the listmerge function.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param string $inList The input list.
	 * @param string $inSep The delimiter seoarating values in the input list.
	 * @param string $fieldSep The optional delimiter seoarating fields in each value.
	 * @param string $token1 The token(s) that represents where the list value should go for item 1.
	 * @param string $token2 The token(s) that represents where the list value should go for item 2.
	 * @param string $tokenSep The separator between tokens if used.
	 * @param string $matchPattern The pattern that determines if items match.
	 * @param string $mergePattern The pattern that list values are inserted into at that token.
	 * @param string $outSep The delimiter that should separate values in the output list.
	 * @param int $sortMode What sort mode to use, if any.
	 * @param int $sortOptions Options for the sort as handled by #listsort.
	 * @param string $countToken The token to replace with the list count. Null/empty to skip.
	 * @param string $intro Content to include before outputted list values, if at least one item is output.
	 * @param string $outro Content to include after outputted list values, if at least one item is output.
	 * @param string $default Content to output if no list values are.
	 * @return string The function output.
	 */
	private static function mergeListByPattern(
		Parser $parser,
		PPFrame $frame,
		string $inList,
		string $inSep,
		string $fieldSep,
		string $token1,
		string $token2,
		string $tokenSep,
		string $matchPattern,
		string $mergePattern,
		string $outSep,
		int $sortMode,
		int $sortOptions,
		string $countToken,
		string $intro,
		string $outro,
		string $default
	): string {
		if ( $inList === '' ) {
			return ParserPower::evaluateUnescaped( $parser, $frame, $default );
		}

		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );

		$sorter = new ListSorter( $sortOptions );

		$inValues = self::explodeList( $inSep, $inList );

		if ( $sortMode & self::SORTMODE_PRE ) {
			$inValues = $sorter->sort( $inValues );
		}

		if ( $tokenSep !== '' ) {
			$tokens1 = array_map( 'trim', explode( $tokenSep, $token1 ) );
			$tokens2 = array_map( 'trim', explode( $tokenSep, $token2 ) );
		} else {
			$tokens1 = [ $token1 ];
			$tokens2 = [ $token2 ];
		}

		$matchParams = [ $parser, $frame, '', '', $fieldSep, $tokens1, $tokens2, $matchPattern ];
		$mergeParams = [ $parser, $frame, '', '', $fieldSep, $tokens1, $tokens2, $mergePattern ];
		$outValues = self::iterativeListMerge(
			$parser,
			$frame,
			$inValues,
			[ __CLASS__, 'applyTwoSetFieldPattern' ],
			$matchParams,
			$mergeParams,
			2,
			3
		);

		if ( $sortMode & ( self::SORTMODE_POST | self::SORTMODE_COMPAT ) ) {
			$outValues = $sorter->sort( $outValues );
		}

		if ( count( $outValues ) === 0 ) {
			return ParserPower::evaluateUnescaped( $parser, $frame, $default );
		}

		$count = count( $outValues );
		$outList = self::applyIntroAndOutro( $intro, implode( $outSep, $outValues ), $outro, $countToken, $count );
		return ParserPower::evaluateUnescaped( $parser, $frame, $outList );
	}

	/**
	 * This function performs the template changing option for the listmerge function.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param string $inList The input list.
	 * @param string $matchTemplate The template to use for the matching test.
	 * @param string $mergeTemplate The template to use for the merging operation.
	 * @param string $inSep The delimiter seoarating values in the input list.
	 * @param string $fieldSep The optional delimiter seoarating fields in each value.
	 * @param string $outSep The delimiter that should separate values in the output list.
	 * @param int $sortMode What sort mode to use, if any.
	 * @param int $sortOptions Options for the sort as handled by #listsort.
	 * @param string $countToken The token to replace with the list count. Null/empty to skip.
	 * @param string $intro Content to include before outputted list values, if at least one item is output.
	 * @param string $outro Content to include after outputted list values, if at least one item is output.
	 * @param string $default Content to output if no list values are.
	 * @return string The function output.
	 */
	private static function mergeListByTemplate(
		Parser $parser,
		PPFrame $frame,
		string $inList,
		string $matchTemplate,
		string $mergeTemplate,
		string $inSep,
		string $fieldSep,
		string $outSep,
		int $sortMode,
		int $sortOptions,
		string $countToken,
		string $intro,
		string $outro,
		string $default
	): string {
		if ( $inList === '' ) {
			return ParserPower::evaluateUnescaped( $parser, $frame, $default );
		}

		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );

		$sorter = new ListSorter( $sortOptions );

		$inValues = self::explodeList( $inSep, $inList );

		if ( $sortMode & self::SORTMODE_PRE ) {
			$inValues = $sorter->sort( $inValues );
		}

		$matchParams = [ $parser, $frame, '', '', $matchTemplate, $fieldSep ];
		$mergeParams = [ $parser, $frame, '', '', $mergeTemplate, $fieldSep ];
		$outValues = self::iterativeListMerge(
			$parser,
			$frame,
			$inValues,
			[ __CLASS__, 'applyTemplateToTwoValues' ],
			$matchParams,
			$mergeParams,
			2,
			3
		);

		if ( $sortMode & ( self::SORTMODE_POST | self::SORTMODE_COMPAT ) ) {
			$outValues = $sorter->sort( $outValues );
		}

		if ( count( $outValues ) === 0 ) {
			return ParserPower::evaluateUnescaped( $parser, $frame, $default );
		}

		$count = count( $outValues );
		$outList = self::applyIntroAndOutro( $intro, implode( $outSep, $outValues ), $outro, $countToken, $count );
		return ParserPower::evaluateUnescaped( $parser, $frame, $outList );
	}

	/**
	 * This function renders the listmerge function, sending it to the appropriate processing function based on what
	 * parameter values are provided.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return string The function output.
	 */
	public function listmergeRender( Parser $parser, PPFrame $frame, array $params ): string {
		$params = ParserPower::arrangeParams( $frame, $params );

		$inList = ParserPower::expand( $frame, $params["list"] ?? '' );
		$default = ParserPower::expand( $frame, $params["default"] ?? '', ParserPower::UNESCAPE );

		$matchTemplate = ParserPower::expand( $frame, $params["matchtemplate"] ?? '' );
		$mergeTemplate = ParserPower::expand( $frame, $params["mergetemplate"] ?? '' );
		$inSep = ParserPower::expand( $frame, $params["insep"] ?? ',', ParserPower::UNESCAPE );
		$fieldSep = ParserPower::expand( $frame, $params["fieldsep"] ?? '', ParserPower::UNESCAPE );
		$token1 = ParserPower::expand( $frame, $params["token1"] ?? '', ParserPower::UNESCAPE );
		$token2 = ParserPower::expand( $frame, $params["token2"] ?? '', ParserPower::UNESCAPE );
		$tokenSep = ParserPower::expand( $frame, $params["tokensep"] ?? ',', ParserPower::UNESCAPE );
		$matchPattern = ParserPower::expand( $frame, $params["matchpattern"] ?? '' );
		$mergePattern = ParserPower::expand( $frame, $params["mergepattern"] ?? '' );
		$outSep = ParserPower::expand( $frame, $params["outsep"] ?? ',\_', ParserPower::UNESCAPE );
		$sortMode = ParserPower::expand( $frame, $params["sortmode"] ?? '' );
		$sortOptions = ParserPower::expand( $frame, $params["sortoptions"] ?? '' );
		$countToken = ParserPower::expand( $frame, $params["counttoken"] ?? '', ParserPower::UNESCAPE );
		$intro = ParserPower::expand( $frame, $params["intro"] ?? '', ParserPower::UNESCAPE );
		$outro = ParserPower::expand( $frame, $params["outro"] ?? '', ParserPower::UNESCAPE );

		if ( $inList === '' ) {
			return ParserPower::evaluateUnescaped( $parser, $frame, $default );
		}

		$sortMode = self::decodeSortMode( $sortMode );
		$sortOptions = self::decodeSortOptions( $sortOptions );

		if ( $matchTemplate !== '' && $mergeTemplate !== '' ) {
			return self::mergeListByTemplate(
				$parser,
				$frame,
				$inList,
				$matchTemplate,
				$mergeTemplate,
				$inSep,
				$fieldSep,
				$outSep,
				$sortMode,
				$sortOptions,
				$countToken,
				$intro,
				$outro,
				$default
			);
		} else {
			return self::mergeListByPattern(
				$parser,
				$frame,
				$inList,
				$inSep,
				$fieldSep,
				$token1,
				$token2,
				$tokenSep,
				$matchPattern,
				$mergePattern,
				$outSep,
				$sortMode,
				$sortOptions,
				$countToken,
				$intro,
				$outro,
				$default
			);
		}
	}
}
