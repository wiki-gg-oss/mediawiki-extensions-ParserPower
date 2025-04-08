<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower;

use Countable;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;
use MediaWiki\Parser\PPNode_Hash_Array;
use StringUtils;

final class ListFunctions {
	/**
	 * Flag for alphanumeric sorting. 0 as this is a default mode.
	 */
	public const SORT_ALPHA = 0;
	/**
	 * Flag for numeric sorting.
	 */
	public const SORT_NUMERIC = 4;
	/**
	 * Flag for case insensitive sorting. 0 as this is a default mode, and ignored in numeric sorts.
	 */
	public const SORT_NCS = 0;
	/**
	 * Flag for case sensitive sorting. 0 as this is a default mode, and ignored in numeric sorts.
	 */
	public const SORT_CS = 2;
	/**
	 * Flag for sorting in ascending order. 0 as this is a default mode.
	 */
	public const SORT_ASC = 0;
	/**
	 * Flag for sorting in descending order.
	 */
	public const SORT_DESC = 1;

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
	public static function decodeBool( $text, $default = false ) {
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
	public static function decodeDuplicates( $text, $default = 0 ) {
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
	public static function decodeCSOption( $text, $default = false ) {
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
	public static function decodeSortMode( $text, $default = 0 ) {
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
	private static function decodeSortOptions( $text, $default = 0 ) {
		$optionKeywords = explode( ' ', $text );
		$options = $default;
		foreach ( $optionKeywords as $optionKeyword ) {
			switch ( strtolower( trim( $optionKeyword ) ) ) {
				case 'numeric':
					$options |= self::SORT_NUMERIC;
					break;
				case 'alpha':
					$options &= ~self::SORT_NUMERIC;
					break;
				case 'cs':
					$options |= self::SORT_CS;
					break;
				case 'ncs':
					$options &= ~self::SORT_CS;
					break;
				case 'desc':
					$options |= self::SORT_DESC;
					break;
				case 'asc':
					$options &= ~self::SORT_DESC;
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
	private static function decodeIndexOptions( $text, $default = 0 ) {
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
	 * This function splits a string of delimited values into an array by a given delimiter or default delimiters.
	 * Whitespaces are trimmed from the end of each value, and empty values are filtered out.
	 *
	 * @param string $sep The delimiter used to separate the strings, or an empty string to use the default delimiters.
	 * @param string $list The list in string format with values separated by the given or default delimiters.
	 * @return array The values in an array of strings.
	 */
	private static function explodeList( $sep, $list ) {
		if ( $sep === '' ) {
			$inValues = preg_split( '/(?<!^)(?!$)/u', $list );
		} else {
			$inValues = StringUtils::explode( $sep, $list );
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
	 * This function gets the specified element from the array after filtering out any empty values before it so that
	 * the empty values are skipped in index counting. The returned element is unescaped.
	 *
	 * @param int $index The 1-based index of the array element to get, or a negative value to start from the end.
	 * @param array $inValues The array to get the element from.
	 * @return string The array element, or empty string if not found.
	 */
	private static function arrayElement( $index, array $inValues ) {
		if ( $index === 0 ) {
			return '';
		}

		$outValues = self::arraySlice( $index, 1, $inValues );
		return $outValues[0] ?? '';
	}

	/**
	 * This function slices an array according to specified offset and length.
	 *
	 * @param int $offset
	 * @param int $length
	 * @param array $inValues The array to slice.
	 * @return array A new array.
	 */
	private static function arraySlice( $offset, $length, array $inValues ) {
		if ( $offset > 0 ) {
			$offset = $offset - 1;
		}

		// If a negative $offset is bigger than $inValues,
		// we need to reduce the number of values array_slice will retrieve.
		if ( $offset < 0 && $length !== null ) {
			$outOfBounds = $offset + count( $inValues );
			if ( $outOfBounds < 0 ) {
				$length = $length + $outOfBounds;
			}
		}

		return array_slice( $inValues, $offset, $length );
	}

	/**
	 * This function directs the counting operation for the lstcnt function.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return string The function output.
	 */
	public function lstcntRender( Parser $parser, PPFrame $frame, array $params ) {
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
	public function lstsepRender( Parser $parser, PPFrame $frame, array $params ) {
		$inList = ParserPower::expand( $frame, $params[0] ?? '' );

		if ( $inList === '' ) {
			return '';
		}

		$inSep = ParserPower::expand( $frame, $params[1] ?? ',', ParserPower::UNESCAPE );
		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );

		$values = self::explodeList( $inSep, $inList );

		if ( count( $values ) < 2 ) {
			$outList = $values[0] ?? '';
		} else {
			$outSep = ParserPower::expand( $frame, $params[2] ?? ',\_', ParserPower::UNESCAPE );
			$outList = implode( $outSep, $values );
		}

		return ParserPower::evaluateUnescaped( $parser, $frame, $outList );
	}

	/**
	 * This function directs the list element retrieval operation for the lstelem function.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return string The function output along with relevant parser options.
	 */
	public function lstelemRender( Parser $parser, PPFrame $frame, array $params ) {
		$inList = ParserPower::expand( $frame, $params[0] ?? '' );

		if ( $inList === '' ) {
			return '';
		}

		$inSep = ParserPower::expand( $frame, $params[1] ?? ',', ParserPower::UNESCAPE );
		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );

		$inValues = self::explodeList( $inSep, $inList );

		if ( count( $inValues ) === 0 ) {
			return '';
		}

		$index = ParserPower::expand( $frame, $params[2] ?? '', ParserPower::UNESCAPE );
		$index = is_numeric( $index ) ? intval( $index ) : 1;

		$value = self::arrayElement( $index, $inValues );

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
	public function lstsubRender( Parser $parser, PPFrame $frame, array $params ) {
		$inList = ParserPower::expand( $frame, $params[0] ?? '' );

		if ( $inList === '' ) {
			return '';
		}

		$inSep = ParserPower::expand( $frame, $params[1] ?? ',', ParserPower::UNESCAPE );
		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );

		$inValues = self::explodeList( $inSep, $inList );

		$inCount = count( $inValues );
		if ( $inCount === 0 ) {
			return '';
		}

		$offset = ParserPower::expand( $frame, $params[3] ?? '', ParserPower::UNESCAPE );
		$offset = is_numeric( $offset ) ? intval( $offset ) : 0;

		if ( $offset >= $inCount ) {
			return '';
		}

		$length = ParserPower::expand( $frame, $params[4] ?? '', ParserPower::UNESCAPE );
		$length = is_numeric( $length ) ? intval( $length ) : null;

		$outValues = self::arraySlice( $offset, $length, $inValues );

		if ( count( $outValues ) < 2 ) {
			$outList = $outValues[0] ?? '';
		} else {
			$outSep = ParserPower::expand( $frame, $params[2] ?? ',\_', ParserPower::UNESCAPE );
			$outList = implode( $outSep, $outValues );
		}

		return ParserPower::evaluateUnescaped( $parser, $frame, $outList );
	}

	/**
	 * This function directs the search operation for the lstfnd function.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return string The function output.
	 */
	public function lstfndRender( Parser $parser, PPFrame $frame, array $params ) {
		$list = ParserPower::expand( $frame, $params[1] ?? '' );

		if ( $list === '' ) {
			return '';
		}

		$sep = ParserPower::expand( $frame, $params[2] ?? ',', ParserPower::UNESCAPE );
		$sep = $parser->getStripState()->unstripNoWiki( $sep );

		$values = self::explodeList( $sep, $list );

		if ( count( $values ) === 0 ) {
			return '';
		}

		$item = ParserPower::expand( $frame, $params[0] ?? '', ParserPower::UNESCAPE );

		$csOption = ParserPower::expand( $frame, $params[3] ?? '' );
		$csOption = self::decodeCSOption( $csOption );

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
	public function lstindRender( Parser $parser, PPFrame $frame, array $params ) {
		$list = ParserPower::expand( $frame, $params[1] ?? '' );

		if ( $list === '' ) {
			return '';
		}

		$sep = ParserPower::expand( $frame, $params[2] ?? ',', ParserPower::UNESCAPE );
		$sep = $parser->getStripState()->unstripNoWiki( $sep );

		$values = self::explodeList( $sep, $list );

		$count = count( $values );
		if ( $count === 0 ) {
			return '';
		}

		$item = ParserPower::expand( $frame, $params[0] ?? '', ParserPower::UNESCAPE );

		$options = ParserPower::expand( $frame, $params[3] ?? '' );
		$options = self::decodeIndexOptions( $options );

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
	public function lstappRender( Parser $parser, PPFrame $frame, array $params ) {
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
	public function lstprepRender( Parser $parser, PPFrame $frame, array $params ) {
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
	public function lstjoinRender( Parser $parser, PPFrame $frame, array $params ) {
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

		$values = array_merge( $values1, $values2 );

		if ( count( $values ) < 2 ) {
			$outList = $values[0] ?? '';
		} else {
			$outSep = ParserPower::expand( $frame, $params[4] ?? ',\_', ParserPower::UNESCAPE );
			$outList = implode( $outSep, $values );
		}

		return ParserPower::evaluateUnescaped( $parser, $frame, $outList );
	}

	/**
	 * Replaces the indicated token in the pattern with the input value.
	 *
	 * @param string $inValue The value to change into one or more template parameters.
	 * @param string $token The token to replace.
	 * @param string $pattern Pattern containing token to be replaced with the input value.
	 * @return string The result of the token replacement within the pattern.
	 */
	private static function applyPattern( $inValue, $token, $pattern ) {
		return ParserPower::applyPattern( $inValue, $token, $pattern );
	}

	/**
	 * Replaces the indicated index token in the pattern with the given index and the token
	 * in the pattern with the input value.
	 *
	 * @param string $inValue The value to change into one or more template parameters.
	 * @param int $indexToken The token to replace with the index, or null/empty value to skip index replacement.
	 * @param int $index The numeric index of this value.
	 * @param string $token The token to replace.
	 * @param string $pattern Pattern containing token to be replaced with the input value.
	 * @return string The result of the token replacement within the pattern.
	 */
	private static function applyPatternWithIndex( $inValue, $indexToken, $index, $token, $pattern ) {
		return ParserPower::applyPatternWithIndex( $inValue, $indexToken, $index, $token, $pattern );
	}

	/**
	 * Breaks the input value into fields and then replaces the indicated tokens in the pattern with those field values.
	 *
	 * @param string $inValue The value to change into one or more template parameters
	 * @param string $fieldSep The delimiter separating the fields in the value.
	 * @param array $tokens The list of tokens to replace.
	 * @param int $tokenCount The number of tokens.
	 * @param string $pattern Pattern containing tokens to be replaced by field values.
	 * @return string The result of the token replacement within the pattern.
	 */
	private static function applyFieldPattern( $inValue, $fieldSep, array $tokens, $tokenCount, $pattern ) {
		return ParserPower::applyFieldPattern( $inValue, $fieldSep, $tokens, $tokenCount, $pattern );
	}

	/**
	 * Replaces the index token with the given index, and then breaks the input value into fields and then replaces the
	 * indicated tokens in the pattern with those field values.
	 *
	 * @param string $inValue The value to change into one or more template parameters
	 * @param string $fieldSep The delimiter separating the fields in the value.
	 * @param int $indexToken The token to replace with the index, or null/empty value to skip index replacement.
	 * @param int $index The numeric index of this value.
	 * @param array $tokens The list of tokens to replace.
	 * @param int $tokenCount The number of tokens.
	 * @param string $pattern Pattern containing tokens to be replaced by field values.
	 * @return string The result of the token replacement within the pattern.
	 */
	private static function applyFieldPatternWithIndex(
		$inValue,
		$fieldSep,
		$indexToken,
		$index,
		array $tokens,
		$tokenCount,
		$pattern
	) {
		return ParserPower::applyFieldPatternWithIndex(
			$inValue,
			$fieldSep,
			$indexToken,
			$index,
			$tokens,
			$tokenCount,
			$pattern
		);
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
	private static function applyIntroAndOutro( $intro, $content, $outro, $countToken, $count ) {
		if ( $countToken !== null && $countToken !== '' ) {
			$intro = str_replace( $countToken, (string)$count, $intro );
			$outro = str_replace( $countToken, (string)$count, $outro );
		}
		return $intro . $content . $outro;
	}

	/**
	 * Turns the input value into one or more template parameters, processes the templates with those parameters, and
	 * returns the result.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param string $inValue The value to change into one or more template parameters.
	 * @param string $template The template to pass the parameters to.
	 * @param string $fieldSep The delimiter separating the parameter values.
	 * @return string The result of the template.
	 */
	private static function applyTemplate( Parser $parser, PPFrame $frame, $inValue, $template, $fieldSep ) {
		if ( $inValue === '' ) {
			return;
		}

		if ( $fieldSep === '' ) {
			$outValue = $frame->virtualBracketedImplode( '{{', '|', '}}', $template, '1=' . $inValue );
		} else {
			$inFields = explode( $fieldSep, $inValue );
			$outFields = [];
			$outFields[] = $template;
			$count = ( is_array( $inFields ) || $inFields instanceof Countable ) ? count( $inFields ) : 0;
			for ( $i = 0; $i < $count; $i++ ) {
				$outFields[] = ( $i + 1 ) . '=' . $inFields[$i];
			}
			$outValue = $frame->virtualBracketedImplode( '{{', '|', '}}', $outFields );
		}

		if ( $outValue instanceof PPNode_Hash_Array ) {
			$outValue = $outValue->value;
		}
		$outValue = implode( '', $outValue );

		$outValue = $parser->preprocessToDom( $outValue, $frame->isTemplate() ? Parser::PTD_FOR_INCLUSION : 0 );
		return ParserPower::expand( $frame, $outValue );
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
	private static function filterListByInclusion( array $inValues, $values, $valueSep, $valueCS ) {
		if ( $valueSep !== '' ) {
			$includeValues = self::explodeList( $valueSep, $values );
		} else {
			$includeValues = [ ParserPower::unescape( $values ) ];
		}

		$outValues = [];

		if ( $valueCS ) {
			foreach ( $inValues as $inValue ) {
				if ( in_array( $inValue, $includeValues ) === true ) {
					$outValues[] = $inValue;
				}
			}
		} else {
			$includeValues = array_map( 'strtolower', $includeValues );
			foreach ( $inValues as $inValue ) {
				if ( in_array( strtolower( $inValue ), $includeValues ) === true ) {
					$outValues[] = $inValue;
				}
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
	private static function filterListByExclusion( array $inValues, $values, $valueSep, $valueCS ) {
		if ( $valueSep !== '' ) {
			$excludeValues = self::explodeList( $valueSep, $values );
		} else {
			$excludeValues = [ ParserPower::unescape( $values ) ];
		}

		$outValues = [];

		if ( $valueCS ) {
			foreach ( $inValues as $inValue ) {
				if ( in_array( $inValue, $excludeValues ) === false ) {
					$outValues[] = $inValue;
				}
			}
		} else {
			$excludeValues = array_map( 'strtolower', $excludeValues );
			foreach ( $inValues as $inValue ) {
				if ( in_array( strtolower( $inValue ), $excludeValues ) === false ) {
					$outValues[] = $inValue;
				}
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
		$fieldSep,
		$indexToken,
		$token,
		$tokenSep,
		$pattern
	) {
		$outValues = [];
		if ( $fieldSep !== '' && $tokenSep !== '' ) {
			$tokens = array_map( 'trim', explode( $tokenSep, $token ) );
			$tokenCount = count( $tokens );
			$index = 1;
			foreach ( $inValues as $value ) {
				if ( $value !== '' ) {
					$result = self::applyFieldPatternWithIndex(
						$value,
						$fieldSep,
						$indexToken,
						$index,
						$tokens,
						$tokenCount,
						$pattern
					);
					$result = ParserPower::unescape( $result );
					$result = ParserPower::evaluateUnescaped( $parser, $frame, $result, ParserPower::WITH_ARGS );
					if ( strtolower( $result ) !== 'remove' ) {
						$outValues[] = $value;
					}
					++$index;
				}
			}
		} else {
			$index = 1;
			foreach ( $inValues as $value ) {
				if ( $value !== '' ) {
					$result = self::applyPatternWithIndex( $value, $indexToken, $index, $token, $pattern );
					$result = ParserPower::unescape( $result );
					$result = ParserPower::evaluateUnescaped( $parser, $frame, $result, ParserPower::WITH_ARGS );
					if ( strtolower( $result ) !== 'remove' ) {
						$outValues[] = $value;
					}
					++$index;
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
	private static function filterFromListByTemplate( Parser $parser, PPFrame $frame, array $inValues, $template, $fieldSep ) {
		$outValues = [];
		foreach ( $inValues as $value ) {
			$result = self::applyTemplate( $parser, $frame, $value, $template, $fieldSep );
			if ( $value !== '' && strtolower( $result ) !== 'remove' ) {
				$outValues[] = $value;
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
	public function listfilterRender( Parser $parser, PPFrame $frame, array $params ) {
		$params = ParserPower::arrangeParams( $frame, $params );

		$inList = ParserPower::expand( $frame, $params["list"] ?? '' );

		if ( $inList === '' ) {
			$default = ParserPower::expand( $frame, $params["default"] ?? '', ParserPower::UNESCAPE );
			return ParserPower::evaluateUnescaped( $parser, $frame, $default );
		}

		$inSep = ParserPower::expand( $frame, $params["insep"] ?? ',', ParserPower::UNESCAPE );
		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );

		$inValues = self::explodeList( $inSep, $inList );

		if ( count( $inValues ) === 0 ) {
			$default = ParserPower::expand( $frame, $params["default"] ?? '', ParserPower::UNESCAPE );
			return ParserPower::evaluateUnescaped( $parser, $frame, $default );
		}

		$keepValues = ParserPower::expand( $frame, $params["keep"] ?? '' );
		$keepSep = ParserPower::expand( $frame, $params["keepsep"] ?? ',' );
		$keepCS = ParserPower::expand( $frame, $params["keepcs"] ?? '' );
		$keepCS = self::decodeBool( $keepCS );
		$removeValues = ParserPower::expand( $frame, $params["remove"] ?? '' );
		$removeSep = ParserPower::expand( $frame, $params["removesep"] ?? ',' );
		$removeCS = ParserPower::expand( $frame, $params["removecs"] ?? '' );
		$removeCS = self::decodeBool( $removeCS );
		$template = ParserPower::expand( $frame, $params["template"] ?? '' );
		$fieldSep = ParserPower::expand( $frame, $params["fieldsep"] ?? '', ParserPower::UNESCAPE );
		$indexToken = ParserPower::expand( $frame, $params["indextoken"] ?? '', ParserPower::UNESCAPE );
		$token = ParserPower::expand( $frame, $params["token"] ?? '', ParserPower::UNESCAPE );
		$tokenSep = ParserPower::expand( $frame, $params["tokensep"] ?? ',', ParserPower::UNESCAPE );
		$tokenSep = $parser->getStripState()->unstripNoWiki( $tokenSep );
		$pattern = ParserPower::expand( $frame, $params["pattern"] ?? '', ParserPower::NO_VARS );

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

		$count = count( $outValues );
		if ( $count === 0 ) {
			$default = ParserPower::expand( $frame, $params["default"] ?? '', ParserPower::UNESCAPE );
			return ParserPower::evaluateUnescaped( $parser, $frame, $default );
		}

		if ( $count === 1 ) {
			$outList = $outValues[0];
		} else {
			$outSep = ParserPower::expand( $frame, $params["outsep"] ?? ',\_', ParserPower::UNESCAPE );
			$outList = implode( $outSep, $outValues );
		}

		$countToken = ParserPower::expand( $frame, $params["counttoken"] ?? '', ParserPower::UNESCAPE );
		$intro = ParserPower::expand( $frame, $params["intro"] ?? '', ParserPower::UNESCAPE );
		$outro = ParserPower::expand( $frame, $params["outro"] ?? '', ParserPower::UNESCAPE );
		$outList = self::applyIntroAndOutro( $intro, $outList, $outro, $countToken, $count );

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
	public function lstfltrRender( Parser $parser, PPFrame $frame, array $params ) {
		$inList = ParserPower::expand( $frame, $params[2] ?? '' );

		if ( $inList === '' ) {
			return '';
		}

		$inSep = ParserPower::expand( $frame, $params[3] ?? ',', ParserPower::UNESCAPE );
		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );

		$inValues = self::explodeList( $inSep, $inList );

		if ( count( $inValues ) === 0 ) {
			return '';
		}

		$values = ParserPower::expand( $frame, $params[0] ?? '' );

		$valueSep = ParserPower::expand( $frame, $params[1] ?? ',', ParserPower::UNESCAPE );

		$csOption = ParserPower::expand( $frame, $params[5] ?? '' );
		$csOption = self::decodeCSOption( $csOption );

		$outValues = self::filterListByInclusion( $inValues, $values, $valueSep, $csOption );

		if ( count( $outValues ) < 2 ) {
			$outList = $outValues[0] ?? '';
		} else {
			$outSep = ParserPower::expand( $frame, $params[4] ?? ',\_', ParserPower::UNESCAPE );
			$outList = implode( $outSep, $outValues );
		}

		return ParserPower::evaluateUnescaped( $parser, $frame, $outList );
	}

	/**
	 * This function renders the lstrm function.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return string The function output.
	 */
	public function lstrmRender( Parser $parser, PPFrame $frame, array $params ) {
		$inList = ParserPower::expand( $frame, $params[1] ?? '' );

		if ( $inList === '' ) {
			return '';
		}

		$inSep = ParserPower::expand( $frame, $params[2] ?? ',', ParserPower::UNESCAPE );
		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );

		$inValues = self::explodeList( $inSep, $inList );

		if ( count( $inValues ) === 0 ) {
			return '';
		}

		$value = ParserPower::expand( $frame, $params[0] ?? '' );

		$csOption = ParserPower::expand( $frame, $params[4] ?? '' );
		$csOption = self::decodeCSOption( $csOption );

		$outValues = self::filterListByExclusion( $inValues, $value, '', $csOption );

		if ( count( $outValues ) < 2 ) {
			$outList = $outValues[0] ?? '';
		} else {
			$outSep = ParserPower::expand( $frame, $params[3] ?? ',\_', ParserPower::UNESCAPE );
			$outList = implode( $outSep, $outValues );
		}

		return ParserPower::evaluateUnescaped( $parser, $frame, $outList );
	}

	/**
	 * This function reduces an array to unique values.
	 *
	 * @param array $values The array of values to reduce to unique values.
	 * @param bool $valueCS true to determine uniqueness case-sensitively, false to determine it case-insensitively
	 * @return string The function output.
	 */
	private static function reduceToUniqueValues( array $values, $valueCS ) {
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
	public function lstcntuniqRender( Parser $parser, PPFrame $frame, array $params ) {
		$inList = ParserPower::expand( $frame, $params[0] ?? '' );

		if ( $inList === '' ) {
			return '0';
		}

		$sep = ParserPower::expand( $frame, $params[1] ?? ',', ParserPower::UNESCAPE );
		$sep = $parser->getStripState()->unstripNoWiki( $sep );

		$values = self::explodeList( $sep, $inList );

		if ( count( $values ) === 0 ) {
			return '0';
		}

		$csOption = ParserPower::expand( $frame, $params[2] ?? '' );
		$csOption = self::decodeCSOption( $csOption );

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
	 * @param array|null $tokens Or if there are mulitple fields, the tokens representing where they go.
	 * @param string $pattern The pattern of text containing token that list values are inserted into at that token.
	 * @return array An array with only values that generated unique keys via the given pattern.
	 */
	private static function reduceToUniqueValuesByKeyPattern(
		Parser $parser,
		PPFrame $frame,
		array $inValues,
		$fieldSep,
		$indexToken,
		$token,
		$tokens,
		$pattern
	) {
		$previousKeys = [];
		$outValues = [];
		if ( ( isset( $tokens ) && is_array( $tokens ) ) ) {
			$tokenCount = count( $tokens );
			$index = 1;
			foreach ( $inValues as $value ) {
				if ( $value !== '' ) {
					$key = self::applyFieldPatternWithIndex(
						$value,
						$fieldSep,
						$indexToken,
						$index,
						$tokens,
						$tokenCount,
						$pattern
					);
					$key = ParserPower::unescape( $key );
					$key = ParserPower::evaluateUnescaped( $parser, $frame, $key, ParserPower::WITH_ARGS );
					if ( !in_array( $key, $previousKeys ) ) {
						$previousKeys[] = $key;
						$outValues[] = $value;
					}
					++$index;
				}
			}
		} else {
			$index = 1;
			foreach ( $inValues as $value ) {
				if ( $value !== '' ) {
					$key = self::applyPatternWithIndex( $value, $indexToken, $index, $token, $pattern );
					$key = ParserPower::unescape( $key );
					$key = ParserPower::evaluateUnescaped( $parser, $frame, $key, ParserPower::WITH_ARGS );
					if ( !in_array( $key, $previousKeys ) ) {
						$previousKeys[] = $key;
						$outValues[] = $value;
					}
					++$index;
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
		$template,
		$fieldSep
	) {
		$previousKeys = [];
		$outValues = [];
		foreach ( $inValues as $value ) {
			$key = self::applyTemplate( $parser, $frame, $value, $template, $fieldSep );
			if ( !in_array( $key, $previousKeys ) ) {
				$previousKeys[] = $key;
				$outValues[] = $value;
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
	public function listuniqueRender( Parser $parser, PPFrame $frame, array $params ) {
		$params = ParserPower::arrangeParams( $frame, $params );

		$inList = ParserPower::expand( $frame, $params["list"] ?? '' );

		if ( $inList === '' ) {
			$default = ParserPower::expand( $frame, $params["default"] ?? '', ParserPower::UNESCAPE );
			return ParserPower::evaluateUnescaped( $parser, $frame, $default );
		}

		$inSep = ParserPower::expand( $frame, $params["insep"] ?? ',', ParserPower::UNESCAPE );

		$inValues = self::explodeList( $inSep, $inList );

		if ( count( $inValues ) === 0 ) {
			$default = ParserPower::expand( $frame, $params["default"] ?? '', ParserPower::UNESCAPE );
			return ParserPower::evaluateUnescaped( $parser, $frame, $default );
		}

		$uniqueCS = ParserPower::expand( $frame, $params["uniquecs"] ?? '' );
		$uniqueCS = self::decodeBool( $uniqueCS );
		$template = ParserPower::expand( $frame, $params["template"] ?? '' );
		$fieldSep = ParserPower::expand( $frame, $params["fieldsep"] ?? '', ParserPower::UNESCAPE );
		$indexToken = ParserPower::expand( $frame, $params["indextoken"] ?? '', ParserPower::UNESCAPE );
		$token = ParserPower::expand( $frame, $params["token"] ?? '', ParserPower::UNESCAPE );
		$tokenSep = ParserPower::expand( $frame, $params["tokensep"] ?? ',', ParserPower::UNESCAPE );
		$pattern = ParserPower::expand( $frame, $params["pattern"] ?? '' );

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
		if ( $count < 2 ) {
			$outList = $outValues[0] ?? '';
		} else {
			$outSep = ParserPower::expand( $frame, $params["outsep"] ?? ',\_', ParserPower::UNESCAPE );
			$outList = implode( $outSep, $outValues );
		}

		$countToken = ParserPower::expand( $frame, $params["counttoken"] ?? '', ParserPower::UNESCAPE );
		$intro = ParserPower::expand( $frame, $params["intro"] ?? '', ParserPower::UNESCAPE );
		$outro = ParserPower::expand( $frame, $params["outro"] ?? '', ParserPower::UNESCAPE );
		$outList = self::applyIntroAndOutro( $intro, $outList, $outro, $countToken, $count );

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
	public function lstuniqRender( Parser $parser, PPFrame $frame, array $params ) {
		$inList = ParserPower::expand( $frame, $params[0] ?? '' );

		if ( $inList === '' ) {
			return '';
		}

		$inSep = ParserPower::expand( $frame, $params[1] ?? ',', ParserPower::UNESCAPE );
		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );

		$values = self::explodeList( $inSep, $inList );

		if ( count( $values ) === 0 ) {
			return '';
		}

		$csOption = ParserPower::expand( $frame, $params[3] ?? '' );
		$csOption = self::decodeCSOption( $csOption );

		$values = self::reduceToUniqueValues( $values, $csOption );

		if ( count( $values ) < 2 ) {
			$outList = $values[0] ?? '';
		} else {
			$outSep = ParserPower::expand( $frame, $params[2] ?? ',\_', ParserPower::UNESCAPE );
			$outList = implode( $outSep, $values );
		}

		return ParserPower::evaluateUnescaped( $parser, $frame, $outList );
	}

	/**
	 * This function sorts an array according to the parameters supplied.
	 *
	 * @param array $values An array of values to sort.
	 * @param int $options The sorting options parameter value as provided by the user.
	 * @return array The values in an array of strings.
	 */
	private static function sortList( array $values, $options ) {
		if ( $options & self::SORT_NUMERIC ) {
			if ( $options & self::SORT_DESC ) {
				rsort( $values, SORT_NUMERIC );
				return $values;
			} else {
				sort( $values, SORT_NUMERIC );
				return $values;
			}
		} else {
			if ( $options & self::SORT_CS ) {
				if ( $options & self::SORT_DESC ) {
					rsort( $values, SORT_STRING );
					return $values;
				} else {
					sort( $values, SORT_STRING );
					return $values;
				}
			} else {
				if ( $options & self::SORT_DESC ) {
					usort( $values, [ ComparisonUtils::class, 'rstrcasecmp' ] );
					return $values;
				} else {
					usort( $values, 'strcasecmp' );
					return $values;
				}
			}
		}
	}

	/**
	 * Generates the sort keys by replacing tokens in a pattern with the fields in the values. This returns an array
	 * of the values where each element is an array with the sort key in element 0 and the value in element 1.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $values The input list.
	 * @param string $fieldSep Separator between fields, if any.
	 * @param int $indexToken
	 * @param string $token The token in the pattern that represents where the list value should go.
	 * @param array|null $tokens Or if there are mulitple fields, the tokens representing where they go.
	 * @param string $pattern The pattern of text containing token that list values are inserted into at that token.
	 * @return array An array where each value has been paired with a sort key in a two-element array.
	 */
	private static function generateSortKeysByPattern(
		Parser $parser,
		PPFrame $frame,
		array $values,
		$fieldSep,
		$indexToken,
		$token,
		$tokens,
		$pattern
	) {
		$pairedValues = [];
		if ( ( isset( $tokens ) && is_array( $tokens ) ) ) {
			$tokenCount = count( $tokens );
			$index = 1;
			foreach ( $values as $value ) {
				if ( $value !== '' ) {
					$key = self::applyFieldPatternWithIndex(
						$value,
						$fieldSep,
						$indexToken,
						$index,
						$tokens,
						$tokenCount,
						$pattern
					);
					$key = ParserPower::unescape( $key );
					$key = ParserPower::evaluateUnescaped( $parser, $frame, $key, ParserPower::WITH_ARGS );
					$pairedValues[] = [ $key, $value ];
					++$index;
				}
			}
		} else {
			$index = 1;
			foreach ( $values as $value ) {
				if ( $value !== '' ) {
					$key = self::applyPatternWithIndex( $value, $indexToken, $index, $token, $pattern );
					$key = ParserPower::unescape( $key );
					$key = ParserPower::evaluateUnescaped( $parser, $frame, $key, ParserPower::WITH_ARGS );
					$pairedValues[] = [ $key, $value ];
					++$index;
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
	private static function generateSortKeysByTemplate( Parser $parser, PPFrame $frame, array $values, $template, $fieldSep ) {
		$pairedValues = [];
		foreach ( $values as $value ) {
			$pairedValues[] = [ self::applyTemplate( $parser, $frame, $value, $template, $fieldSep ), $value ];
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
	private static function discardSortKeys( array $pairedValues ) {
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
	private static function discardValues( array $pairedValues ) {
		$values = [];

		foreach ( $pairedValues as $pairedValue ) {
			$values[] = '<nowiki>' . $pairedValue[0] . '</nowiki>';
		}

		return $values;
	}

	/**
	 * This function directs the sort operation for the listsort function.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return string The function output.
	 */
	public function listsortRender( Parser $parser, PPFrame $frame, array $params ) {
		$params = ParserPower::arrangeParams( $frame, $params );

		$inList = ParserPower::expand( $frame, $params["list"] ?? '' );

		if ( $inList === '' ) {
			$default = ParserPower::expand( $frame, $params["default"] ?? '', ParserPower::UNESCAPE );
			return ParserPower::evaluateUnescaped( $parser, $frame, $default );
		}

		$inSep = ParserPower::expand( $frame, $params["insep"] ?? ',', ParserPower::UNESCAPE );
		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );

		$values = self::explodeList( $inSep, $inList );

		if ( count( $values ) === 0 ) {
			$default = ParserPower::expand( $frame, $params["default"] ?? '', ParserPower::UNESCAPE );
			return ParserPower::evaluateUnescaped( $parser, $frame, $default );
		}

		$template = ParserPower::expand( $frame, $params["template"] ?? '' );
		$fieldSep = ParserPower::expand( $frame, $params["fieldsep"] ?? '', ParserPower::UNESCAPE );
		$indexToken = ParserPower::expand( $frame, $params["indextoken"] ?? '', ParserPower::UNESCAPE );
		$token = ParserPower::expand( $frame, $params["token"] ?? '', ParserPower::UNESCAPE );
		$tokenSep = ParserPower::expand( $frame, $params["tokensep"] ?? ',', ParserPower::UNESCAPE );
		$pattern = ParserPower::expand( $frame, $params["pattern"] ?? '' );
		$sortOptions = ParserPower::expand( $frame, $params["sortoptions"] ?? '' );
		$subsort = ParserPower::expand( $frame, $params["subsort"] ?? '' );
		$subsort = self::decodeBool( $subsort );
		$subsortOptions = ParserPower::expand( $frame, $params["subsortoptions"] ?? '' );
		$subsortOptions = self::decodeSortOptions( $subsortOptions );
		$duplicates = ParserPower::expand( $frame, $params["duplicates"] ?? '' );
		$duplicates = self::decodeDuplicates( $duplicates );

		if ( $duplicates & self::DUPLICATES_STRIP ) {
			$values = array_unique( $values );
		}

		if ( $fieldSep !== '' && $tokenSep !== '' ) {
			$tokens = array_map( 'trim', explode( $tokenSep, $token ) );
		}

		if ( $template !== '' ) {
			$sortOptions = self::decodeSortOptions( $sortOptions, self::SORT_NUMERIC );

			$pairedValues = self::generateSortKeysByTemplate( $parser, $frame, $values, $template, $fieldSep );

			usort( $pairedValues, [ new SortKeyValueComparer( $sortOptions, $subsort, $subsortOptions ), 'compare' ] );
			$values = self::discardSortKeys( $pairedValues );
		} else if ( ( $indexToken !== '' || $token !== '' ) && $pattern !== '' ) {
			$sortOptions = self::decodeSortOptions( $sortOptions, self::SORT_NUMERIC );

			$pairedValues = self::generateSortKeysByPattern(
				$parser,
				$frame,
				$values,
				$fieldSep,
				$indexToken,
				$token,
				$tokens ?? null,
				$pattern
			);

			usort( $pairedValues, [ new SortKeyValueComparer( $sortOptions, $subsort, $subsortOptions ), 'compare' ] );
			$values = self::discardSortKeys( $pairedValues );
		} else {
			$sortOptions = self::decodeSortOptions( $sortOptions );
			$values = self::sortList( $values, $sortOptions );
		}

		$count = count( $values );
		if ( $count === 0 ) {
			$default = ParserPower::expand( $frame, $params["default"] ?? '', ParserPower::UNESCAPE );
			return ParserPower::evaluateUnescaped( $parser, $frame, $default );
		}

		if ( $count === 1 ) {
			$outList = $values[0];
		} else {
			$outSep = ParserPower::expand( $frame, $params["outsep"] ?? ',\_', ParserPower::UNESCAPE );
			$outList = implode( $outSep, $values );
		}

		$countToken = ParserPower::expand( $frame, $params["counttoken"] ?? '', ParserPower::UNESCAPE );
		$intro = ParserPower::expand( $frame, $params["intro"] ?? '', ParserPower::UNESCAPE );
		$outro = ParserPower::expand( $frame, $params["outro"] ?? '', ParserPower::UNESCAPE );
		$outList = self::applyIntroAndOutro( $intro, $outList, $outro, $countToken, $count );

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
	public function lstsrtRender( Parser $parser, PPFrame $frame, array $params ) {
		$inList = ParserPower::expand( $frame, $params[0] ?? '' );

		if ( $inList === '' ) {
			return '';
		}

		$inSep = ParserPower::expand( $frame, $params[1] ?? ',', ParserPower::UNESCAPE );
		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );

		$values = self::explodeList( $inSep, $inList );

		if ( count( $values ) === 0 ) {
			return '';
		}

		$sortOptions = ParserPower::expand( $frame, $params[3] ?? '' );
		$sortOptions = self::decodeSortOptions( $sortOptions );

		$values = self::sortList( $values, $sortOptions );

		if ( count( $values ) < 2 ) {
			$outList = $values[0] ?? '';
		} else {
			$outSep = ParserPower::expand( $frame, $params[2] ?? ',\_', ParserPower::UNESCAPE );
			$outList = implode( $outSep, $values );
		}

		return ParserPower::evaluateUnescaped( $parser, $frame, $outList );
	}

	/**
	 * This function performs the pattern changing operation for the listmap function.
	 *
	 * @param array $inValues The input list.
	 * @param string $fieldSep The optional delimiter seoarating fields in each value.
	 * @param string $indexToken Replace the current 1-based index of the element. Null/empty to skip.
	 * @param string $token The token(s) in the pattern that represents where the list value should go.
	 * @param string $tokenSep The separator between tokens if used.
	 * @param string $pattern The pattern containing token that list values are inserted into at that token.
	 * @return string The function output.
	 */
	private static function applyPatternToList( $inValues, $fieldSep, $indexToken, $token, $tokenSep, $pattern ) {
		$outValues = [];
		$index = 1;
		if ( $fieldSep !== '' && $tokenSep !== '' ) {
			$tokens = array_map( 'trim', explode( $tokenSep, $token ) );
			$tokenCount = count( $tokens );
			foreach ( $inValues as $inValue ) {
				if ( $inValue !== '' ) {
					$outValue = self::applyFieldPatternWithIndex(
						$inValue,
						$fieldSep,
						$indexToken,
						$index,
						$tokens,
						$tokenCount,
						$pattern
					);
					$outValue = ParserPower::unescape( $outValue );
					if ( $outValue !== '' ) {
						$outValues[] = $outValue;
						++$index;
					}
				}
			}
		} else {
			foreach ( $inValues as $inValue ) {
				if ( $inValue !== '' ) {
					$outValue = self::applyPatternWithIndex( $inValue, $indexToken, $index, $token, $pattern );
					$outValue = ParserPower::unescape( $outValue );
					if ( $outValue !== '' ) {
						$outValues[] = $outValue;
						++$index;
					}
				}
			}
		}
		return $outValues;
	}

	/**
	 * This function performs the sort option for the listmtemp function.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $inValues The input list.
	 * @param string $template The template to use.
	 * @param string $fieldSep The optional delimiter seoarating fields in each value.
	 * @return string The function output.
	 */
	private static function applyTemplateToList( Parser $parser, PPFrame $frame, $inValues, $template, $fieldSep ) {
		$outValues = [];
		foreach ( $inValues as $inValue ) {
			$outValues[] = self::applyTemplate( $parser, $frame, $inValue, $template, $fieldSep );
		}
		return $outValues;
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
	public function listmapRender( Parser $parser, PPFrame $frame, array $params ) {
		$params = ParserPower::arrangeParams( $frame, $params );

		$inList = ParserPower::expand( $frame, $params["list"] ?? '' );

		if ( $inList === '' ) {
			$default = ParserPower::expand( $frame, $params["default"] ?? '', ParserPower::UNESCAPE );
			return ParserPower::evaluateUnescaped( $parser, $frame, $default );
		}

		$inSep = ParserPower::expand( $frame, $params["insep"] ?? ',', ParserPower::UNESCAPE );
		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );

		$inValues = self::explodeList( $inSep, $inList );

		if ( count( $inValues ) === 0 ) {
			$default = ParserPower::expand( $frame, $params["default"] ?? '', ParserPower::UNESCAPE );
			return ParserPower::evaluateUnescaped( $parser, $frame, $default );
		}

		$template = ParserPower::expand( $frame, $params["template"] ?? '' );
		$fieldSep = ParserPower::expand( $frame, $params["fieldsep"] ?? '', ParserPower::UNESCAPE );
		$indexToken = ParserPower::expand( $frame, $params["indextoken"] ?? '', ParserPower::UNESCAPE );
		$token = ParserPower::expand( $frame, $params["token"] ?? '', ParserPower::UNESCAPE );
		$tokenSep = ParserPower::expand( $frame, $params["tokensep"] ?? ',', ParserPower::UNESCAPE );
		$pattern = ParserPower::expand( $frame, $params["pattern"] ?? '' );
		$sortMode = ParserPower::expand( $frame, $params["sortmode"] ?? '' );
		$sortMode = self::decodeSortMode( $sortMode );
		$sortOptions = ParserPower::expand( $frame, $params["sortoptions"] ?? '' );
		$sortOptions = self::decodeSortOptions( $sortOptions );
		$duplicates = ParserPower::expand( $frame, $params["duplicates"] ?? '' );
		$duplicates = self::decodeDuplicates( $duplicates );

		if ( $duplicates & self::DUPLICATES_PRESTRIP ) {
			$inValues = array_unique( $inValues );
		}

		if ( $template !== '' ) {
			if ( $sortMode & self::SORTMODE_PRE ) {
				$inValues = self::sortList( $inValues, $sortOptions );
			}

			$outValues = self::applyTemplateToList( $parser, $frame, $inValues, $template, $fieldSep );

			if ( $sortMode & ( self::SORTMODE_POST | self::SORTMODE_COMPAT ) ) {
				$outValues = self::sortList( $outValues, $sortOptions );
			}
		} else {
			if ( ( $indexToken !== '' && $sortMode & self::SORTMODE_COMPAT ) || $sortMode & self::SORTMODE_PRE ) {
				$inValues = self::sortList( $inValues, $sortOptions );
			}

			$outValues = self::applyPatternToList( $inValues, $fieldSep, $indexToken, $token, $tokenSep, $pattern );

			if ( ( $indexToken === '' && $sortMode & self::SORTMODE_COMPAT ) || $sortMode & self::SORTMODE_POST ) {
				$outValues = self::sortList( $outValues, $sortOptions );
			}
		}

		if ( $duplicates & self::DUPLICATES_POSTSTRIP ) {
			$outValues = array_unique( $outValues );
		}

		$count = count( $outValues );
		if ( $count === 0 ) {
			$default = ParserPower::expand( $frame, $params["default"] ?? '', ParserPower::UNESCAPE );
			return ParserPower::evaluateUnescaped( $parser, $frame, $default );
		}

		if ( $count === 1 ) {
			$outList = $outValues[0];
		} else {
			$outSep = ParserPower::expand( $frame, $params["outsep"] ?? ',\_', ParserPower::UNESCAPE );
			$outList = implode( $outSep, $outValues );
		}

		$countToken = ParserPower::expand( $frame, $params["counttoken"] ?? '', ParserPower::UNESCAPE );
		$intro = ParserPower::expand( $frame, $params["intro"] ?? '', ParserPower::UNESCAPE );
		$outro = ParserPower::expand( $frame, $params["outro"] ?? '', ParserPower::UNESCAPE );
		$outList = self::applyIntroAndOutro( $intro, $outList, $outro, $countToken, $count );

		return ParserPower::evaluateUnescaped( $parser, $frame, $outList );
	}

	/**
	 * This function performs the sort option for the listm function.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return string The function output.
	 */
	public function lstmapRender( Parser $parser, PPFrame $frame, array $params ) {
		$inList = ParserPower::expand( $frame, $params[0] ?? '' );

		if ( $inList === '' ) {
			return '';
		}

		$inSep = ParserPower::expand( $frame, $params[1] ?? ',', ParserPower::UNESCAPE );
		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );
	
		$inValues = self::explodeList( $inSep, $inList );

		if ( count( $inValues ) === 0 ) {
			return '';
		}

		$token = ParserPower::expand( $frame, $params[2] ?? 'x', ParserPower::UNESCAPE );
		$pattern = ParserPower::expand( $frame, $params[3] ?? 'x' );
		$sortMode = ParserPower::expand( $frame, $params[5] ?? '' );
		$sortMode = self::decodeSortMode( $sortMode );
		$sortOptions = ParserPower::expand( $frame, $params[6] ?? '' );
		$sortOptions = self::decodeSortOptions( $sortOptions );

		if ( $sortMode & self::SORTMODE_PRE ) {
			$inValues = self::sortList( $inValues, $sortOptions );
		}

		$outValues = self::applyPatternToList( $inValues, '', '', $token, '', $pattern );

		if ( $sortMode & ( self::SORTMODE_POST | self::SORTMODE_COMPAT ) ) {
			$outValues = self::sortList( $outValues, $sortOptions );
		}

		if ( count( $outValues ) < 2 ) {
			$outList = $outValues[0] ?? '';
		} else {
			$outSep = ParserPower::expand( $frame, $params[4] ?? ',\_', ParserPower::UNESCAPE );
			$outList = implode( $outSep, $outValues );
		}

		return ParserPower::evaluateUnescaped( $parser, $frame, $outList );
	}

	/**
	 * This function performs the sort option for the lstmaptemp function.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return string The function output.
	 */
	public function lstmaptempRender( Parser $parser, PPFrame $frame, array $params ) {
		$inList = ParserPower::expand( $frame, $params[0] ?? '' );

		if ( $inList === '' ) {
			return '';
		}

		$inSep = ParserPower::expand( $frame, $params[2] ?? ',', ParserPower::UNESCAPE );
		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );

		$inValues = self::explodeList( $inSep, $inList );

		if ( count( $inValues ) === 0 ) {
			return '';
		}

		$template = ParserPower::expand( $frame, $params[1] ?? '' );
		$sortMode = ParserPower::expand( $frame, $params[4] ?? '' );
		$sortMode = self::decodeSortMode( $sortMode );
		$sortOptions = ParserPower::expand( $frame, $params[5] ?? '' );
		$sortOptions = self::decodeSortOptions( $sortOptions );

		if ( $sortMode & self::SORTMODE_PRE ) {
			$inValues = self::sortList( $inValues, $sortOptions );
		}

		if ( $template === '' ) {
			$outValues = self::applyPatternToList( $inValues, '', '', '', '', '' );
		} else {
			$outValues = self::applyTemplateToList( $parser, $frame, $inValues, $template, '' );
		}

		if ( $sortMode & ( self::SORTMODE_POST | self::SORTMODE_COMPAT ) ) {
			$outValues = self::sortList( $outValues, $sortOptions );
		}

		if ( count( $outValues ) < 2 ) {
			$outList = $outValues[0] ?? '';
		} else {
			$outSep = ParserPower::expand( $frame, $params[3] ?? ',\_', ParserPower::UNESCAPE );
			$outList = implode( $outSep, $outValues );
		}

		return ParserPower::evaluateUnescaped( $parser, $frame, $outList );
	}

	/**
	 * Breaks the input values into fields and then replaces the indicated tokens in the pattern
	 * with those field values. This is for special cases when two sets of replacements are
	 * necessary for a given pattern.
	 *
	 * @param string $inValue1 The first value to (potentially) split and replace tokens with
	 * @param string $inValue2 The second value to (potentially) split and replace tokens with
	 * @param string $fieldSep The delimiter separating the fields in the value.
	 * @param array $tokens1 The list of tokens to replace when performing the replacement for $inValue1.
	 * @param array $tokens2 The list of tokens to replace when performing the replacement for $inValue2.
	 * @param string $pattern Pattern containing tokens to be replaced by field (or unsplit) values.
	 * @return string The result of the token replacement within the pattern.
	 */
	private static function applyTwoSetFieldPattern(
		$inValue1,
		$inValue2,
		$fieldSep,
		array $tokens1,
		array $tokens2,
		$pattern
	) {
		$tokenCount1 = count( $tokens1 );
		$tokenCount2 = count( $tokens2 );

		if ( $inValue1 === '' || $inValue2 === '' ) {
			return;
		}

		$outValue = $pattern;
		if ( $fieldSep === '' ) {
			if ( $inValue1 !== '' ) {
				$outValue = str_replace( $tokens1[0], $inValue1, $outValue );
			}
			if ( $inValue2 !== '' ) {
				$outValue = str_replace( $tokens2[0], $inValue2, $outValue );
			}
		} else {
			if ( $inValue1 !== '' ) {
				$fields = explode( $fieldSep, $inValue1, $tokenCount1 );
				$fieldCount = count( $fields );
				for ( $i = 0; $i < $tokenCount1; $i++ ) {
					$outValue = str_replace( $tokens1[$i], ( $i < $fieldCount ) ? $fields[$i] : '', $outValue );
				}
			}
			if ( $inValue2 !== '' ) {
				$fields = explode( $fieldSep, $inValue2, $tokenCount2 );
				$fieldCount = count( $fields );
				for ( $i = 0; $i < $tokenCount2; $i++ ) {
					$outValue = str_replace( $tokens2[$i], ( $i < $fieldCount ) ? $fields[$i] : '', $outValue );
				}
			}
		}
		return $outValue;
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
		$inValue1,
		$inValue2,
		$template,
		$fieldSep
	) {
		if ( $fieldSep === '' ) {
			$fieldSep = '|';
		}
		return self::applyTemplate( $parser, $frame, $inValue1 . $fieldSep . $inValue2, $template, $fieldSep );
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
	 * @param string $applyFunction Valid name of the function to call for both match and merge processes.
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
		$applyFunction,
		array $matchParams,
		array $mergeParams,
		$valueIndex1,
		$valueIndex2
	) {
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
						$doMerge = ParserPower::unescape( $doMerge );
						$doMerge = ParserPower::evaluateUnescaped( $parser, $frame, $doMerge, ParserPower::WITH_ARGS );
						$doMerge = self::decodeBool( $doMerge );
						$checkedPairs[$value1][$value2] = $doMerge;
					}

					if ( $doMerge ) {
						$value1 = call_user_func_array( $applyFunction, $mergeParams );
						$value1 = ParserPower::unescape( $value1 );
						$value1 = ParserPower::evaluateUnescaped( $parser, $frame, $value1, ParserPower::WITH_ARGS );
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
	 * @return string The function output.
	 */
	private static function mergeListByPattern(
		Parser $parser,
		PPFrame $frame,
		$inValues,
		$fieldSep,
		$token1,
		$token2,
		$tokenSep,
		$matchPattern,
		$mergePattern
	) {
		if ( $tokenSep !== '' ) {
			$tokens1 = array_map( 'trim', explode( $tokenSep, $token1 ) );
			$tokens2 = array_map( 'trim', explode( $tokenSep, $token2 ) );
		} else {
			$tokens1 = [ $token1 ];
			$tokens2 = [ $token2 ];
		}

		$matchParams = [ '', '', $fieldSep, $tokens1, $tokens2, $matchPattern ];
		$mergeParams = [ '', '', $fieldSep, $tokens1, $tokens2, $mergePattern ];
		$outValues = self::iterativeListMerge(
			$parser,
			$frame,
			$inValues,
			[ __CLASS__, 'applyTwoSetFieldPattern' ],
			$matchParams,
			$mergeParams,
			0,
			1
		);

		return $outValues;
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
	 * @return string The function output.
	 */
	private static function mergeListByTemplate(
		Parser $parser,
		PPFrame $frame,
		$inValues,
		$matchTemplate,
		$mergeTemplate,
		$fieldSep
	) {
		$matchParams = [ $parser, $frame, null, null, $matchTemplate, $fieldSep ];
		$mergeParams = [ $parser, $frame, null, null, $mergeTemplate, $fieldSep ];
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

		return $outValues;
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
	public function listmergeRender( Parser $parser, PPFrame $frame, array $params ) {
		$params = ParserPower::arrangeParams( $frame, $params );

		$inList = ParserPower::expand( $frame, $params["list"] ?? '' );

		if ( $inList === '' ) {
			$default = ParserPower::expand( $frame, $params["default"] ?? '', ParserPower::UNESCAPE );
			return ParserPower::evaluateUnescaped( $parser, $frame, $default );
		}

		$inSep = ParserPower::expand( $frame, $params["insep"] ?? ',', ParserPower::UNESCAPE );
		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );

		$inValues = self::explodeList( $inSep, $inList );

		if ( count( $inValues ) === 0 ) {
			return '';
		}

		$matchTemplate = ParserPower::expand( $frame, $params["matchtemplate"] ?? '' );
		$mergeTemplate = ParserPower::expand( $frame, $params["mergetemplate"] ?? '' );
		$fieldSep = ParserPower::expand( $frame, $params["fieldsep"] ?? '', ParserPower::UNESCAPE );
		$token1 = ParserPower::expand( $frame, $params["token1"] ?? '', ParserPower::UNESCAPE );
		$token2 = ParserPower::expand( $frame, $params["token2"] ?? '', ParserPower::UNESCAPE );
		$tokenSep = ParserPower::expand( $frame, $params["tokensep"] ?? ',', ParserPower::UNESCAPE );
		$matchPattern = ParserPower::expand( $frame, $params["matchpattern"] ?? '' );
		$mergePattern = ParserPower::expand( $frame, $params["mergepattern"] ?? '' );
		$sortMode = ParserPower::expand( $frame, $params["sortmode"] ?? '' );
		$sortMode = self::decodeSortMode( $sortMode );
		$sortOptions = ParserPower::expand( $frame, $params["sortoptions"] ?? '' );
		$sortOptions = self::decodeSortOptions( $sortOptions );

		if ( $sortMode & self::SORTMODE_PRE ) {
			$inValues = self::sortList( $inValues, $sortOptions );
		}

		if ( $matchTemplate !== '' && $mergeTemplate !== '' ) {
			$outValues = self::mergeListByTemplate( $parser, $frame, $inValues, $matchTemplate, $mergeTemplate, $fieldSep );
		} else {
			$outValues = self::mergeListByPattern(
				$parser,
				$frame,
				$inValues,
				$fieldSep,
				$token1,
				$token2,
				$tokenSep,
				$matchPattern,
				$mergePattern
			);
		}

		if ( $sortMode & ( self::SORTMODE_POST | self::SORTMODE_COMPAT ) ) {
			$outValues = self::sortList( $outValues, $sortOptions );
		}

		$count = count( $outValues );
		if ( $count === 0 ) {
			$default = ParserPower::expand( $frame, $params["default"] ?? '', ParserPower::UNESCAPE );
			return ParserPower::evaluateUnescaped( $parser, $frame, $default );
		}

		if ( $count === 1 ) {
			$outList = $outValues[0];
		} else {
			$outSep = ParserPower::expand( $frame, $params["outsep"] ?? ',\_', ParserPower::UNESCAPE );
			$outList = implode( $outSep, $outValues );
		}

		$countToken = ParserPower::expand( $frame, $params["counttoken"] ?? '', ParserPower::UNESCAPE );
		$intro = ParserPower::expand( $frame, $params["intro"] ?? '', ParserPower::UNESCAPE );
		$outro = ParserPower::expand( $frame, $params["outro"] ?? '', ParserPower::UNESCAPE );
		$outList = self::applyIntroAndOutro( $intro, $outList, $outro, $countToken, $count );

		return ParserPower::evaluateUnescaped( $parser, $frame, $outList );
	}
}
