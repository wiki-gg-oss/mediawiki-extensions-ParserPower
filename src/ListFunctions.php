<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower;

use Countable;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;
use MediaWiki\Parser\PPNode_Hash_Array;

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
	 * Registers the list handling parser functions with the parser.
	 *
	 * @param Parser &$parser The parser object being initialized.
	 * @return void
	 */
	public static function setup( &$parser ) {
		$parser->setFunctionHook( 'lstcnt', [ __CLASS__, 'lstcntRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'lstsep', [ __CLASS__, 'lstsepRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'lstelem', [ __CLASS__, 'lstelemRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'lstsub', [ __CLASS__, 'lstsubRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'lstfnd', [ __CLASS__, 'lstfndRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'lstind', [ __CLASS__, 'lstindRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'lstapp', [ __CLASS__, 'lstappRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'lstprep', [ __CLASS__, 'lstprepRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'lstjoin', [ __CLASS__, 'lstjoinRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'lstcntuniq', [ __CLASS__, 'lstcntuniqRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'listunique', [ __CLASS__, 'listuniqueRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'lstuniq', [ __CLASS__, 'lstuniqRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'listfilter', [ __CLASS__, 'listfilterRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'lstfltr', [ __CLASS__, 'lstfltrRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'lstrm', [ __CLASS__, 'lstrmRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'listsort', [ __CLASS__, 'listsortRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'lstsrt', [ __CLASS__, 'lstsrtRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'listmap', [ __CLASS__, 'listmapRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'lstmap', [ __CLASS__, 'lstmapRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'lstmaptemp', [ __CLASS__, 'lstmaptempRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'listmerge', [ __CLASS__, 'listmergeRender' ], Parser::SFH_OBJECT_ARGS );
	}

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
	 *
	 * @param string $sep The delimiter used to separate the strings, or an empty string to use the default delimiters.
	 * @param string $list The list in string format with values separated by the given or default delimiters.
	 * @return array The values in an array of strings.
	 */
	private static function explodeList( $sep, $list ) {
		if ( $sep === '' ) {
			$values = preg_split( '/(.)/u', $list, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE );
		} else {
			$values = explode( $sep, $list );
		}

		return $values;
	}

	/**
	 * This function gets the specified element from the array after filtering out any empty values before it so that
	 * the empty values are skipped in index counting. The returned element is unescaped.
	 *
	 * @param int $inIndex The 1-based index of the array element to get, or a negative value to start from the end.
	 * @param array|null $inValues The array to get the element from.
	 * @return string The array element, trimmed and with character escapes replaced, or empty string if not found.
	 */
	private static function arrayElementTrimUnescape( $inIndex, $inValues ) {
		if ( $inIndex > 0 ) {
			$curOutIndex = 1;
			$count = ( is_array( $inValues ) || $inValues instanceof Countable ) ? count( $inValues ) : 0;
			for ( $curInIndex = 0; $curInIndex < $count; ++$curInIndex ) {
				$trimmedValue = trim( $inValues[$curInIndex] );
				if ( !ParserPower::isEmpty( $trimmedValue ) ) {
					if ( $inIndex === $curOutIndex ) {
						return ParserPower::unescape( $trimmedValue );
					} else {
						++$curOutIndex;
					}
				}
			}
		} elseif ( $inIndex < 0 ) {
			$curOutIndex = -1;
			for ( $curInIndex = count( $inValues ) - 1; $curInIndex > -1; --$curInIndex ) {
				$trimmedValue = trim( $inValues[$curInIndex] );
				if ( !ParserPower::isEmpty( $trimmedValue ) ) {
					if ( $inIndex === $curOutIndex ) {
						return ParserPower::unescape( $trimmedValue );
					} else {
						--$curOutIndex;
					}
				}
			}
		}

		return '';
	}

	/**
	 * This function trims whitespace each value while also filtering emoty values from the array, then slicing it
	 * according to specified offset and length. It also performs un-escaping on each item. Note that values
	 * that are only empty after the unescape are preserved.
	 *
	 * @param int $inOffset
	 * @param int $inLength
	 * @param array $inValues The array to trim, remove empty values from, slice, and unescape.
	 * @return array A new array with trimmed values, character escapes replaced, and empty values pre unescape removed.
	 */
	private static function arrayTrimSliceUnescape( $inOffset, $inLength, array $inValues ) {
		$midValues = [];
		$outValues = [];

		foreach ( $inValues as $inValue ) {
			$trimmedValue = trim( $inValue );
			if ( !ParserPower::isEmpty( $trimmedValue ) ) {
				$midValues[] = $trimmedValue;
			}
		}

		if ( $inOffset > 0 ) {
			$offset = $inOffset - 1;
		} else {
			$offset = $inOffset;
		}

		if ( $offset < 0 ) {
			$length = -$offset;
		} else {
			$length = count( $midValues ) - $offset;
		}
		if ( $inLength !== null ) {
			$length = intval( $inLength );
		}

		$midValues = array_slice( $midValues, $offset, $length );
		foreach ( $midValues as $midValue ) {
			$outValues[] = ParserPower::unescape( $midValue );
		}

		return $outValues;
	}

	/**
	 * This function trims whitespace from the end of each value while also filter emoty values from the array. It also
	 * performs un-escaping on each item. Note that values that are only empty after the unescape are preserved.
	 *
	 * @param array $inValues The array to trim, unescape, and remove empty values from.
	 * @return array A new array with trimmed values, character escapes replaced, and empty values preunescape removed.
	 */
	private static function arrayTrimUnescape( array $inValues ) {
		$outValues = [];

		foreach ( $inValues as $inValue ) {
			$trimmedValue = trim( $inValue );
			if ( !ParserPower::isEmpty( $trimmedValue ) ) {
				$outValues[] = ParserPower::unescape( $trimmedValue );
			}
		}

		return $outValues;
	}

	/**
	 * This function directs the counting operation for the lstcnt function.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return array The function output along with relevant parser options.
	 */
	public static function lstcntRender( Parser $parser, PPFrame $frame, array $params ) {
		$list = ParserPower::expand( $frame, $params[0] ?? '' );

		if ( $list === '' ) {
			return [ '0', 'noparse' => false ];
		}

		$sep = ParserPower::expand( $frame, $params[1] ?? ',', ParserPower::UNESCAPE );

		$sep = $parser->getStripState()->unstripNoWiki( $sep );

		$count = count( self::arrayTrimUnescape( self::explodeList( $sep, $list ) ) );
		return [ $count, 'noparse' => false ];
	}

	/**
	 * This function directs the delimiter replacement operation for the lstsep function.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return array The function output along with relevant parser options.
	 */
	public static function lstsepRender( Parser $parser, PPFrame $frame, array $params ) {
		$inList = ParserPower::expand( $frame, $params[0] ?? '' );

		if ( $inList === '' ) {
			return [ '', 'noparse' => false ];
		}

		$inSep = ParserPower::expand( $frame, $params[1] ?? ',', ParserPower::UNESCAPE );
		$outSep = ParserPower::expand( $frame, $params[2] ?? ',\_', ParserPower::UNESCAPE );

		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );

		$values = self::arrayTrimUnescape( self::explodeList( $inSep, $inList ) );
		return [ implode( $outSep, $values ), 'noparse' => false ];
	}

	/**
	 * This function directs the list element retrieval operation for the lstelem function.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return array The function output along with relevant parser options.
	 */
	public static function lstelemRender( Parser $parser, PPFrame $frame, array $params ) {
		$inList = ParserPower::expand( $frame, $params[0] ?? '' );

		if ( $inList === '' ) {
			return [ '', 'noparse' => false ];
		}

		$inSep = ParserPower::expand( $frame, $params[1] ?? ',', ParserPower::UNESCAPE );
		$inIndex = ParserPower::expand( $frame, $params[2] ?? '', ParserPower::UNESCAPE );

		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );

		$index = 1;
		if ( is_numeric( $inIndex ) ) {
			$index = intval( $inIndex );
		}

		$value = self::arrayElementTrimUnescape( $index, self::explodeList( $inSep, $inList ) );

		return [ $value, 'noparse' => false ];
	}

	/**
	 * This function directs the list subdivision and delimiter replacement operation for the lstsub function.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return array The function output along with relevant parser options.
	 */
	public static function lstsubRender( Parser $parser, PPFrame $frame, array $params ) {
		$inList = ParserPower::expand( $frame, $params[0] ?? '' );

		if ( $inList === '' ) {
			return [ '', 'noparse' => false ];
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

		$values = self::arrayTrimSliceUnescape( $offset, $length, self::explodeList( $inSep, $inList ) );

		if ( count( $values ) > 0 ) {
			return [ implode( $outSep, $values ), 'noparse' => false ];
		} else {
			return [ '', 'noparse' => false ];
		}
	}

	/**
	 * This function directs the search operation for the lstfnd function.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return array The function output along with relevant parser options.
	 */
	public static function lstfndRender( Parser $parser, PPFrame $frame, array $params ) {
		$list = ParserPower::expand( $frame, $params[1] ?? '' );

		if ( $list === '' ) {
			return [ '', 'noparse' => false ];
		}

		$item = ParserPower::expand( $frame, $params[0] ?? '', ParserPower::UNESCAPE );
		$sep = ParserPower::expand( $frame, $params[2] ?? ',', ParserPower::UNESCAPE );
		$csOption = ParserPower::expand( $frame, $params[3] ?? '' );

		$sep = $parser->getStripState()->unstripNoWiki( $sep );
		$csOption = self::decodeCSOption( $csOption );

		$values = self::arrayTrimUnescape( self::explodeList( $sep, $list ) );
		if ( $csOption ) {
			foreach ( $values as $value ) {
				if ( $value === $item ) {
					return [ $value, 'noparse' => false ];
				}
			}
		} else {
			foreach ( $values as $value ) {
				if ( strtolower( $value ) === strtolower( $item ) ) {
					return [ $value, 'noparse' => false ];
				}
			}
		}
		return [ '', 'noparse' => false ];
	}

	/**
	 * This function directs the search operation for the lstind function.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return array The function output along with relevant parser options.
	 */
	public static function lstindRender( Parser $parser, PPFrame $frame, array $params ) {
		$list = ParserPower::expand( $frame, $params[1] ?? '' );

		if ( $list === '' ) {
			return [ '', 'noparse' => false ];
		}

		$item = ParserPower::expand( $frame, $params[0] ?? '', ParserPower::UNESCAPE );
		$sep = ParserPower::expand( $frame, $params[2] ?? ',', ParserPower::UNESCAPE );
		$inOptions = ParserPower::expand( $frame, $params[3] ?? '' );

		$sep = $parser->getStripState()->unstripNoWiki( $sep );
		$options = self::decodeIndexOptions( $inOptions );

		$values = self::arrayTrimUnescape( self::explodeList( $sep, $list ) );
		$count = ( is_array( $values ) || $values instanceof Countable ) ? count( $values ) : 0;
		if ( $options & self::INDEX_DESC ) {
			if ( $options & self::INDEX_CS ) {
				for ( $index = $count - 1; $index > -1; --$index ) {
					if ( $values[$index] === $item ) {
						return [ strval( ( $options & self::INDEX_NEG ) ? $index - $count : $index + 1 ),
							'noparse' => false
						];
					}
				}
			} else {
				for ( $index = $count - 1; $index > -1; --$index ) {
					if ( strtolower( $values[$index] ) === strtolower( $item ) ) {
						return [ strval( ( $options & self::INDEX_NEG ) ? $index - $count : $index + 1 ),
							'noparse' => false
						];
					}
				}
			}
		} else {
			if ( $options & self::INDEX_CS ) {
				for ( $index = 0; $index < $count; ++$index ) {
					if ( $values[$index] === $item ) {
						return [ strval( ( $options & self::INDEX_NEG ) ? $index - $count : $index + 1 ),
							'noparse' => false
						];
					}
				}
			} else {
				for ( $index = 0; $index < $count; ++$index ) {
					if ( strtolower( $values[$index] ) === strtolower( $item ) ) {
						return [ strval( ( $options & self::INDEX_NEG ) ? $index - $count : $index + 1 ),
							'noparse' => false
						];
					}
				}
			}
		}
		return [ '', 'noparse' => false ];
	}

	/**
	 * This function directs the append operation for the lstapp function.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return array The function output along with relevant parser options.
	 */
	public static function lstappRender( Parser $parser, PPFrame $frame, array $params ) {
		$list = ParserPower::expand( $frame, $params[0] ?? '' );
		$value = ParserPower::expand( $frame, $params[2] ?? '', ParserPower::UNESCAPE );

		if ( $list === '' ) {
			return [ $value, 'noparse' => false ];
		}

		$sep = ParserPower::expand( $frame, $params[1] ?? ',', ParserPower::UNESCAPE );

		$sep = $parser->getStripState()->unstripNoWiki( $sep );

		$values = self::arrayTrimUnescape( self::explodeList( $sep, $list ) );
		if ( $value !== '' ) {
			$values[] = $value;
		}
		return [ implode( $sep, $values ), 'noparse' => false ];
	}

	/**
	 * This function directs the prepend operation for the lstprep function.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return array The function output along with relevant parser options.
	 */
	public static function lstprepRender( Parser $parser, PPFrame $frame, array $params ) {
		$value = ParserPower::expand( $frame, $params[0] ?? '', ParserPower::UNESCAPE );
		$list = ParserPower::expand( $frame, $params[2] ?? '' );

		if ( $list === '' ) {
			return [ $value, 'noparse' => false ];
		}

		$sep = ParserPower::expand( $frame, $params[1] ?? '', ParserPower::UNESCAPE );

		$sep = $parser->getStripState()->unstripNoWiki( $sep );

		$values = self::arrayTrimUnescape( self::explodeList( $sep, $list ) );
		if ( $value !== '' ) {
			array_unshift( $values, $value );
		}
		return [ implode( $sep, $values ), 'noparse' => false ];
	}

	/**
	 * This function directs the joining operation for the lstjoin function.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return array The function output along with relevant parser options.
	 */
	public static function lstjoinRender( Parser $parser, PPFrame $frame, array $params ) {
		$inList1 = ParserPower::expand( $frame, $params[0] ?? '' );
		$inList2 = ParserPower::expand( $frame, $params[2] ?? '' );

		if ( $inList1 === '' && $inList2 === '' ) {
			return [ '', 'noparse' => false ];
		}

		if ( $inList1 === '' ) {
			$values1 = [];
		} else {
			$inSep1 = ParserPower::expand( $frame, $params[1] ?? '', ParserPower::UNESCAPE );
			$inSep1 = $parser->getStripState()->unstripNoWiki( $inSep1 );
			$values1 = self::arrayTrimUnescape( self::explodeList( $inSep1, $inList1 ) );
		}

		if ( $inList2 === '' ) {
			$values2 = [];
		} else {
			$inSep2 = ParserPower::expand( $frame, $params[3] ?? '', ParserPower::UNESCAPE );
			$inSep2 = $parser->getStripState()->unstripNoWiki( $inSep2 );
			$values2 = self::arrayTrimUnescape( self::explodeList( $inSep2, $inList2 ) );
		}

		$outSep = ParserPower::expand( $frame, $params[4] ?? ',\_', ParserPower::UNESCAPE );

		return [ implode( $outSep, array_merge( $values1, $values2 ) ), 'noparse' => false ];
	}

	/**
	 * Replaces the indicated token in the pattern with the input value.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param string $inValue The value to change into one or more template parameters.
	 * @param string $token The token to replace.
	 * @param string $pattern Pattern containing token to be replaced with the input value.
	 * @return string The result of the token replacement within the pattern.
	 */
	private static function applyPattern( Parser $parser, PPFrame $frame, $inValue, $token, $pattern ) {
		return ParserPower::applyPattern( $parser, $frame, $inValue, $token, $pattern );
	}

	/**
	 * Replaces the indicated index token in the pattern with the given index and the token
	 * in the pattern with the input value.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param string $inValue The value to change into one or more template parameters.
	 * @param int $indexToken The token to replace with the index, or null/empty value to skip index replacement.
	 * @param int $index The numeric index of this value.
	 * @param string $token The token to replace.
	 * @param string $pattern Pattern containing token to be replaced with the input value.
	 * @return string The result of the token replacement within the pattern.
	 */
	private static function applyPatternWithIndex(
		Parser $parser,
		PPFrame $frame,
		$inValue,
		$indexToken,
		$index,
		$token,
		$pattern
	) {
		return ParserPower::applyPatternWithIndex( $parser, $frame, $inValue, $indexToken, $index, $token, $pattern );
	}

	/**
	 * Breaks the input value into fields and then replaces the indicated tokens in the pattern with those field values.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param string $inValue The value to change into one or more template parameters
	 * @param string $fieldSep The delimiter separating the fields in the value.
	 * @param array $tokens The list of tokens to replace.
	 * @param int $tokenCount The number of tokens.
	 * @param string $pattern Pattern containing tokens to be replaced by field values.
	 * @return string The result of the token replacement within the pattern.
	 */
	private static function applyFieldPattern(
		Parser $parser,
		PPFrame $frame,
		$inValue,
		$fieldSep,
		array $tokens,
		$tokenCount,
		$pattern
	) {
		return ParserPower::applyFieldPattern( $parser, $frame, $inValue, $fieldSep, $tokens, $tokenCount, $pattern );
	}

	/**
	 * Replaces the index token with the given index, and then breaks the input value into fields and then replaces the
	 * indicated tokens in the pattern with those field values.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
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
		Parser $parser,
		PPFrame $frame,
		$inValue,
		$fieldSep,
		$indexToken,
		$index,
		array $tokens,
		$tokenCount,
		$pattern
	) {
		return ParserPower::applyFieldPatternWithIndex(
			$parser,
			$frame,
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
			$intro = str_replace( $countToken, strval( $count ), $intro );
			$outro = str_replace( $countToken, strval( $count ), $outro );
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
		$inValue = trim( $inValue );
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
		return $parser->replaceVariables( implode( '', $outValue ), $frame );
	}

	/**
	 * This function performs the filtering operation for the listfiler function when done by value inclusion.
	 *
	 * @param array $inValues Array with the input values.
	 * @param string $values The list of values to include, not yet exploded.
	 * @param string $valueSep The delimiter separating the values to include.
	 * @param bool $valueCS true to match in a case-sensitive manner, false to match in a case-insensitive manner
	 * @return array The function output along with relevant parser options.
	 */
	private static function filterListByInclusion( array $inValues, $values, $valueSep, $valueCS ) {
		if ( $valueSep !== '' ) {
			$includeValues = self::arrayTrimUnescape( self::explodeList( $valueSep, $values ) );
		} else {
			$includeValues = [ ParserPower::unescape( trim( $values ) ) ];
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
	 * @return array The function output along with relevant parser options.
	 */
	private static function filterListByExclusion( array $inValues, $values, $valueSep, $valueCS ) {
		if ( $valueSep !== '' ) {
			$excludeValues = self::arrayTrimUnescape( self::explodeList( $valueSep, $values ) );
		} else {
			$excludeValues = [ ParserPower::unescape( trim( $values ) ) ];
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
	 * @return array The function output along with relevant parser options.
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
				if ( trim( $value ) !== '' ) {
					$result = self::applyFieldPatternWithIndex(
						$parser,
						$frame,
						$value,
						$fieldSep,
						$indexToken,
						$index,
						$tokens,
						$tokenCount,
						$pattern
					);
					$result = $parser->replaceVariables( ParserPower::unescape( trim( $result ) ), $frame );
					if ( strtolower( $result ) !== 'remove' ) {
						$outValues[] = $value;
					}
					++$index;
				}
			}
		} else {
			$index = 1;
			foreach ( $inValues as $value ) {
				if ( trim( $value ) !== '' ) {
					$result = self::applyPatternWithIndex(
						$parser,
						$frame,
						$value,
						$indexToken,
						$index,
						$token,
						$pattern
					);
					$result = $parser->replaceVariables( ParserPower::unescape( $result ), $frame );
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
	 * @return array The function output along with relevant parser options.
	 */
	public static function listfilterRender( Parser $parser, PPFrame $frame, array $params ) {
		$params = ParserPower::arrangeParams( $frame, $params );

		$inList = ParserPower::expand( $frame, $params["list"] ?? '' );
		$default = ParserPower::expand( $frame, $params["default"] ?? '', ParserPower::UNESCAPE );

		if ( $inList === '' ) {
			return [ $default, 'noparse' => false ];
		}

		$keepValues = ParserPower::expand( $frame, $params["keep"] ?? '' );
		$keepSep = ParserPower::expand( $frame, $params["keepsep"] ?? ',' );
		$keepCS = self::decodeBool( ParserPower::expand( $frame, $params["keepcs"] ?? '' ) );
		$removeValues = ParserPower::expand( $frame, $params["remove"] ?? '' );
		$removeSep = ParserPower::expand( $frame, $params["removesep"] ?? ',' );
		$removeCS = self::decodeBool( ParserPower::expand( $frame, $params["removecs"] ?? '' ) );
		$template = ParserPower::expand( $frame, $params["template"] ?? '' );
		$inSep = ParserPower::expand( $frame, $params["insep"] ?? ',', ParserPower::UNESCAPE );
		$fieldSep = ParserPower::expand( $frame, $params["fieldsep"] ?? '', ParserPower::UNESCAPE );
		$indexToken = ParserPower::expand( $frame, $params["indextoken"] ?? '', ParserPower::NO_VARS | ParserPower::UNESCAPE );
		$token = ParserPower::expand( $frame, $params["token"] ?? '', ParserPower::NO_VARS | ParserPower::UNESCAPE );
		$tokenSep = ParserPower::expand( $frame, $params["tokensep"] ?? ',', ParserPower::UNESCAPE );
		$pattern = $params["pattern"] ?? '';
		$outSep = ParserPower::expand( $frame, $params["outsep"] ?? ',\_', ParserPower::UNESCAPE );
		$countToken = ParserPower::expand( $frame, $params["counttoken"] ?? '', ParserPower::NO_VARS | ParserPower::UNESCAPE );
		$intro = ParserPower::expand( $frame, $params["intro"] ?? '', ParserPower::UNESCAPE );
		$outro = ParserPower::expand( $frame, $params["outro"] ?? '', ParserPower::UNESCAPE );

		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );
		$tokenSep = $parser->getStripState()->unstripNoWiki( $tokenSep );

		$inValues = self::arrayTrimUnescape( self::explodeList( $inSep, $inList ) );

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
			return [ $default, 'noparse' => false ];
		}

		$outList = implode( $outSep, $outValues );
		$count = strval( count( $outValues ) );
		return [ self::applyIntroAndOutro( $intro, $outList, $outro, $countToken, $count ), 'noparse' => false ];
	}

	/**
	 * This function renders the lstfltr function.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return array The function output along with relevant parser options.
	 */
	public static function lstfltrRender( Parser $parser, PPFrame $frame, array $params ) {
		$inList = ParserPower::expand( $frame, $params[2] ?? '' );

		if ( $inList === '' ) {
			return [ '', 'noparse' => false ];
		}

		$values = ParserPower::expand( $frame, $params[0] ?? '' );
		$valueSep = ParserPower::expand( $frame, $params[1] ?? ',', ParserPower::UNESCAPE );
		$inSep = ParserPower::expand( $frame, $params[3] ?? ',', ParserPower::UNESCAPE );
		$outSep = ParserPower::expand( $frame, $params[4] ?? ',\_', ParserPower::UNESCAPE );
		$csOption = ParserPower::expand( $frame, $params[5] ?? '' );

		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );
		$csOption = self::decodeCSOption( $csOption );

		$inValues = self::arrayTrimUnescape( self::explodeList( $inSep, $inList ) );

		$outValues = self::filterListByInclusion( $inValues, $values, $valueSep, $csOption );

		if ( count( $outValues ) > 0 ) {
			return [ implode( $outSep, $outValues ), 'noparse' => false ];
		} else {
			return [ '', 'noparse' => false ];
		}
	}

	/**
	 * This function renders the lstrm function.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return array The function output along with relevant parser options.
	 */
	public static function lstrmRender( Parser $parser, PPFrame $frame, array $params ) {
		$inList = ParserPower::expand( $frame, $params[1] ?? '' );

		if ( $inList === '' ) {
			return [ '', 'noparse' => false ];
		}

		$value = ParserPower::expand( $frame, $params[0] ?? '' );
		$inSep = ParserPower::expand( $frame, $params[2] ?? ',', ParserPower::UNESCAPE );
		$outSep = ParserPower::expand( $frame, $params[3] ?? ',\_', ParserPower::UNESCAPE );
		$csOption = ParserPower::expand( $frame, $params[4] ?? '' );

		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );
		$csOption = self::decodeCSOption( $csOption );

		$inValues = self::arrayTrimUnescape( self::explodeList( $inSep, $inList ) );

		$outValues = self::filterListByExclusion( $inValues, $value, '', $csOption );

		if ( count( $outValues ) > 0 ) {
			return [ implode( $outSep, $outValues ), 'noparse' => false ];
		} else {
			return [ '', 'noparse' => false ];
		}
	}

	/**
	 * This function reduces an array to unique values.
	 *
	 * @param array $values The array of values to reduce to unique values.
	 * @param bool $valueCS true to determine uniqueness case-sensitively, false to determine it case-insensitively
	 * @return array The function output along with relevant parser options.
	 */
	public static function reduceToUniqueValues( array $values, $valueCS ) {
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
	 * @return array The function output along with relevant parser options.
	 */
	public static function lstcntuniqRender( Parser $parser, PPFrame $frame, array $params ) {
		$inList = ParserPower::expand( $frame, $params[0] ?? '' );

		if ( $inList === '' ) {
			return [ '0', 'noparse' => false ];
		}

		$sep = ParserPower::expand( $frame, $params[1] ?? ',', ParserPower::UNESCAPE );
		$csOption = ParserPower::expand( $frame, $params[2] ?? '' );

		$sep = $parser->getStripState()->unstripNoWiki( $sep );
		$csOption = self::decodeCSOption( $csOption );

		$values = self::arrayTrimUnescape( self::explodeList( $sep, $inList ) );
		$values = self::reduceToUniqueValues( $values, $csOption );
		return [ strval( count( $values ) ), 'noparse' => false ];
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
				if ( trim( $value ) !== '' ) {
					$key = self::applyFieldPatternWithIndex(
						$parser,
						$frame,
						$value,
						$fieldSep,
						$indexToken,
						$index,
						$tokens,
						$tokenCount,
						$pattern
					);
					$key = $parser->replaceVariables( ParserPower::unescape( $key ), $frame );
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
				if ( trim( $value ) !== '' ) {
					$key = self::applyPatternWithIndex( $parser, $frame, $value, $indexToken, $index, $token, $pattern );
					$key = $parser->replaceVariables( ParserPower::unescape( $key ), $frame );
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
	 * @return array The function output along with relevant parser options.
	 */
	public static function listuniqueRender( Parser $parser, PPFrame $frame, array $params ) {
		$params = ParserPower::arrangeParams( $frame, $params );

		$inList = ParserPower::expand( $frame, $params["list"] ?? '' );
		$default = ParserPower::expand( $frame, $params["default"] ?? '', ParserPower::UNESCAPE );

		if ( $inList === '' ) {
			return [ $default, 'noparse' => false ];
		}

		$uniqueCS = ParserPower::expand( $frame, $params["uniquecs"] ?? '' );
		$template = ParserPower::expand( $frame, $params["template"] ?? '' );
		$inSep = ParserPower::expand( $frame, $params["insep"] ?? ',', ParserPower::UNESCAPE );
		$fieldSep = ParserPower::expand( $frame, $params["fieldsep"] ?? '', ParserPower::UNESCAPE );
		$indexToken = ParserPower::expand( $frame, $params["indextoken"] ?? '', ParserPower::NO_VARS | ParserPower::UNESCAPE );
		$token = ParserPower::expand( $frame, $params["token"] ?? '', ParserPower::NO_VARS | ParserPower::UNESCAPE );
		$tokenSep = ParserPower::expand( $frame, $params["tokensep"] ?? ',', ParserPower::UNESCAPE );
		$pattern = $params["pattern"] ?? '';
		$outSep = ParserPower::expand( $frame, $params["outsep"] ?? ',\_', ParserPower::UNESCAPE );
		$countToken = ParserPower::expand( $frame, $params["counttoken"] ?? '', ParserPower::NO_VARS | ParserPower::UNESCAPE );
		$intro = ParserPower::expand( $frame, $params["intro"] ?? '', ParserPower::UNESCAPE );
		$outro = ParserPower::expand( $frame, $params["outro"] ?? '', ParserPower::UNESCAPE );

		$uniqueCS = self::decodeBool( $uniqueCS );

		$inValues = self::arrayTrimUnescape( self::explodeList( $inSep, $inList ) );

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
		$outList = implode( $outSep, $outValues );
		$count = strval( count( $outValues ) );
		return [ self::applyIntroAndOutro( $intro, $outList, $outro, $countToken, $count ), 'noparse' => false ];
	}

	/**
	 * This function directs the duplicate removal function for the lstuniq function.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return array The function output along with relevant parser options.
	 */
	public static function lstuniqRender( Parser $parser, PPFrame $frame, array $params ) {
		$inList = ParserPower::expand( $frame, $params[0] ?? '' );

		if ( $inList === '' ) {
			return [ '', 'noparse' => false ];
		}

		$inSep = ParserPower::expand( $frame, $params[1] ?? ',', ParserPower::UNESCAPE );
		$outSep = ParserPower::expand( $frame, $params[2] ?? ',\_', ParserPower::UNESCAPE );
		$csOption = ParserPower::expand( $frame, $params[3] ?? '' );

		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );
		$csOption = self::decodeCSOption( $csOption );

		$values = self::arrayTrimUnescape( self::explodeList( $inSep, $inList ) );
		$values = self::reduceToUniqueValues( $values, $csOption );
		return [ implode( $outSep, $values ), 'noparse' => false ];
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
				if ( trim( $value ) !== '' ) {
					$key = self::applyFieldPatternWithIndex(
						$parser,
						$frame,
						$value,
						$fieldSep,
						$indexToken,
						$index,
						$tokens,
						$tokenCount,
						$pattern
					);
					$key = $parser->replaceVariables( ParserPower::unescape( $key ), $frame );
					$pairedValues[] = [ $key, $value ];
					++$index;
				}
			}
		} else {
			$index = 1;
			foreach ( $values as $value ) {
				if ( trim( $value ) !== '' ) {
					$key = self::applyPatternWithIndex( $parser, $frame, $value, $indexToken, $index, $token, $pattern );
					$key = $parser->replaceVariables( ParserPower::unescape( $key ), $frame );
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
	 * Sorts a list by keys
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $values The input list.
	 * @param string $template The template to use.
	 * @param string $fieldSep The delimiter separating values in the input list.
	 * @param string $indexToken Replace the current 1-based index of the element. Null/empty to skip.
	 * @param string $token The token in the pattern that represents where the list value should go.
	 * @param array|null $tokens Or if there are mulitple fields, the tokens representing where they go.
	 * @param string $pattern The pattern containing token that list values are inserted into at that token.
	 * @param int $sortOptions Options for the key sort as handled by #listsort.
	 * @param bool $subsort Whether to perform a value sort where sort keys are equal.
	 * @param int $subsortOptions Options for the value sort as handled by #listsort.
	 * @return array An array where each value has been paired with a sort key in a two-element array.
	 */
	private static function sortListByKeys(
		Parser $parser,
		PPFrame $frame,
		array $values,
		$template,
		$fieldSep,
		$indexToken,
		$token,
		$tokens,
		$pattern,
		$sortOptions,
		$subsort,
		$subsortOptions
	) {
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

		$comparer = new SortKeyValueComparer( $sortOptions, $subsort, $subsortOptions );

		usort( $pairedValues, [ $comparer, 'compare' ] );

		return self::discardSortKeys( $pairedValues );
	}

	/**
	 * This function directs the sort operation for the listsort function.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return array The function output along with relevant parser options.
	 */
	public static function listsortRender( Parser $parser, PPFrame $frame, array $params ) {
		$params = ParserPower::arrangeParams( $frame, $params );

		$inList = ParserPower::expand( $frame, $params["list"] ?? '' );
		$default = ParserPower::expand( $frame, $params["default"] ?? '', ParserPower::UNESCAPE );

		if ( $inList === '' ) {
			return [ $default, 'noparse' => false ];
		}

		$template = ParserPower::expand( $frame, $params["template"] ?? '' );
		$inSep = ParserPower::expand( $frame, $params["insep"] ?? ',', ParserPower::UNESCAPE );
		$fieldSep = ParserPower::expand( $frame, $params["fieldsep"] ?? '', ParserPower::UNESCAPE );
		$indexToken = ParserPower::expand( $frame, $params["indextoken"] ?? '', ParserPower::NO_VARS | ParserPower::UNESCAPE );
		$token = ParserPower::expand( $frame, $params["token"] ?? '', ParserPower::NO_VARS | ParserPower::UNESCAPE );
		$tokenSep = ParserPower::expand( $frame, $params["tokensep"] ?? ',', ParserPower::UNESCAPE );
		$pattern = $params["pattern"] ?? '';
		$outSep = ParserPower::expand( $frame, $params["outsep"] ?? ',\_', ParserPower::UNESCAPE );
		$sortOptions = ParserPower::expand( $frame, $params["sortoptions"] ?? '' );
		$subsort = ParserPower::expand( $frame, $params["subsort"] ?? '' );
		$subsortOptions = ParserPower::expand( $frame, $params["subsortoptions"] ?? '' );
		$duplicates = ParserPower::expand( $frame, $params["duplicates"] ?? '' );
		$countToken = ParserPower::expand( $frame, $params["counttoken"] ?? '', ParserPower::NO_VARS | ParserPower::UNESCAPE );
		$intro = ParserPower::expand( $frame, $params["intro"] ?? '', ParserPower::UNESCAPE );
		$outro = ParserPower::expand( $frame, $params["outro"] ?? '', ParserPower::UNESCAPE );

		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );
		$subsort = self::decodeBool( $subsort );
		$subsortOptions = self::decodeSortOptions( $subsortOptions );
		$duplicates = self::decodeDuplicates( $duplicates );

		$values = self::arrayTrimUnescape( self::explodeList( $inSep, $inList ) );
		if ( $duplicates & self::DUPLICATES_STRIP ) {
			$values = array_unique( $values );
		}

		if ( $fieldSep !== '' && $tokenSep !== '' ) {
			$tokens = array_map( 'trim', explode( $tokenSep, $token ) );
		}

		if ( $template !== '' || ( ( $indexToken !== '' || $token !== '' ) && $pattern !== '' ) ) {
			$sortOptions = self::decodeSortOptions( $sortOptions, self::SORT_NUMERIC );
			$values = self::sortListByKeys(
				$parser,
				$frame,
				$values,
				$template,
				$fieldSep,
				$indexToken,
				$token,
				$tokens ?? null,
				$pattern,
				$sortOptions,
				$subsort,
				$subsortOptions
			);
		} else {
			$sortOptions = self::decodeSortOptions( $sortOptions );
			$values = self::sortList( $values, $sortOptions );
		}

		if ( count( $values ) === 0 ) {
			return [ $default, 'noparse' => false ];
		}

		$outList = implode( $outSep, $values );
		$count = strval( count( $values ) );
		return [ self::applyIntroAndOutro( $intro, $outList, $outro, $countToken, $count ), 'noparse' => false ];
	}

	/**
	 * This function directs the sort option for the lstsrt function.
	 *
	 * @param Parser $parser The parser object. Ignored.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return array The function output along with relevant parser options.
	 */
	public static function lstsrtRender( Parser $parser, PPFrame $frame, array $params ) {
		$inList = ParserPower::expand( $frame, $params[0] ?? '' );

		if ( $inList === '' ) {
			return [ '', 'noparse' => false ];
		}

		$inSep = ParserPower::expand( $frame, $params[1] ?? ',', ParserPower::UNESCAPE );
		$outSep = ParserPower::expand( $frame, $params[2] ?? ',\_', ParserPower::UNESCAPE );
		$sortOptions = ParserPower::expand( $frame, $params[3] ?? '' );

		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );
		$sortOptions = self::decodeSortOptions( $sortOptions );

		$values = self::arrayTrimUnescape( self::explodeList( $inSep, $inList ) );
		$values = self::sortList( $values, $sortOptions );
		return [ implode( $outSep, $values ), 'noparse' => false ];
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
	 * @return array The function output along with relevant parser options.
	 */
	private static function applyPatternToList(
		Parser $parser,
		PPFrame $frame,
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
	) {
		if ( $inList === '' ) {
			return [ $default, 'noparse' => false ];
		}

		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );

		$inValues = self::arrayTrimUnescape( self::explodeList( $inSep, $inList ) );

		if ( $duplicates & self::DUPLICATES_PRESTRIP ) {
			$inValues = array_unique( $inValues );
		}

		if ( ( $indexToken !== '' && $sortMode & self::SORTMODE_COMPAT ) || $sortMode & self::SORTMODE_PRE ) {
			$inValues = self::sortList( $inValues, $sortOptions );
		}

		$outValues = [];
		$index = 1;
		if ( $fieldSep !== '' && $tokenSep !== '' ) {
			$tokens = array_map( 'trim', explode( $tokenSep, $token ) );
			$tokenCount = count( $tokens );
			foreach ( $inValues as $inValue ) {
				if ( trim( $inValue ) !== '' ) {
					$outValue = self::applyFieldPatternWithIndex(
						$parser,
						$frame,
						$inValue,
						$fieldSep,
						$indexToken,
						$index,
						$tokens,
						$tokenCount,
						$pattern
					);
					if ( $outValue !== '' ) {
						$outValues[] = $outValue;
						++$index;
					}
				}
			}
		} else {
			foreach ( $inValues as $inValue ) {
				if ( trim( $inValue ) !== '' ) {
					$outValue = self::applyPatternWithIndex( $parser, $frame, $inValue, $indexToken, $index, $token, $pattern );
					if ( $outValue !== '' ) {
						$outValues[] = $outValue;
						++$index;
					}
				}
			}
		}

		if ( $duplicates & self::DUPLICATES_POSTSTRIP ) {
			$outValues = array_unique( $outValues );
		}

		if ( ( $indexToken === '' && $sortMode & self::SORTMODE_COMPAT ) || $sortMode & self::SORTMODE_POST ) {
			$outValues = self::sortList( $outValues, $sortOptions );
		}

		if ( count( $outValues ) === 0 ) {
			return [ $default, 'noparse' => false ];
		}

		if ( $countToken !== null && $countToken !== '' ) {
			$intro = str_replace( $countToken, strval( count( $outValues ) ), $intro );
			$outro = str_replace( $countToken, strval( count( $outValues ) ), $outro );
		}
		return [ $intro . implode( $outSep, $outValues ) . $outro, 'noparse' => false ];
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
	 * @return array The function output along with relevant parser options.
	 */
	private static function applyTemplateToList(
		Parser $parser,
		PPFrame $frame,
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
	) {
		if ( $inList === '' ) {
			return [ $default, 'noparse' => false ];
		}

		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );

		$inValues = self::arrayTrimUnescape( self::explodeList( $inSep, $inList ) );
		if ( $duplicates & self::DUPLICATES_PRESTRIP ) {
			$inValues = array_unique( $inValues );
		}

		if ( $sortMode & self::SORTMODE_PRE ) {
			$inValues = self::sortList( $inValues, $sortOptions );
		}

		$outValues = [];
		foreach ( $inValues as $inValue ) {
			$outValues[] = self::applyTemplate( $parser, $frame, $inValue, $template, $fieldSep );
		}

		if ( $sortMode & ( self::SORTMODE_POST | self::SORTMODE_COMPAT ) ) {
			$outValues = self::sortList( $outValues, $sortOptions );
		}

		if ( $duplicates & self::DUPLICATES_POSTSTRIP ) {
			$outValues = array_unique( $outValues );
		}

		if ( count( $outValues ) === 0 ) {
			return [ $default, 'noparse' => false ];
		}

		$outList = implode( $outSep, $outValues );
		$count = strval( count( $outValues ) );
		return [ self::applyIntroAndOutro( $intro, $outList, $outro, $countToken, $count ), 'noparse' => false ];
	}

	/**
	 * This function renders the listmap function, sending it to the appropriate processing function based on what
	 * parameter values are provided.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return array The function output along with relevant parser options.
	 */
	public static function listmapRender( Parser $parser, PPFrame $frame, array $params ) {
		$params = ParserPower::arrangeParams( $frame, $params );

		$inList = ParserPower::expand( $frame, $params["list"] ?? '' );
		$default = ParserPower::expand( $frame, $params["default"] ?? '', ParserPower::UNESCAPE );

		if ( $inList === '' ) {
			return [ $default, 'noparse' => false ];
		}

		$template = ParserPower::expand( $frame, $params["template"] ?? '' );
		$inSep = ParserPower::expand( $frame, $params["insep"] ?? ',', ParserPower::UNESCAPE );
		$fieldSep = ParserPower::expand( $frame, $params["fieldsep"] ?? '', ParserPower::UNESCAPE );
		$indexToken = ParserPower::expand( $frame, $params["indextoken"] ?? '', ParserPower::NO_VARS | ParserPower::UNESCAPE );
		$token = ParserPower::expand( $frame, $params["token"] ?? '', ParserPower::NO_VARS | ParserPower::UNESCAPE );
		$tokenSep = ParserPower::expand( $frame, $params["tokensep"] ?? ',', ParserPower::UNESCAPE );
		$pattern = $params["pattern"] ?? '';
		$outSep = ParserPower::expand( $frame, $params["outsep"] ?? ',\_', ParserPower::UNESCAPE );
		$sortMode = ParserPower::expand( $frame, $params["sortmode"] ?? '' );
		$sortOptions = ParserPower::expand( $frame, $params["sortoptions"] ?? '' );
		$duplicates = ParserPower::expand( $frame, $params["duplicates"] ?? '' );
		$countToken = ParserPower::expand( $frame, $params["counttoken"] ?? '', ParserPower::NO_VARS | ParserPower::UNESCAPE );
		$intro = ParserPower::expand( $frame, $params["intro"] ?? '', ParserPower::UNESCAPE );
		$outro = ParserPower::expand( $frame, $params["outro"] ?? '', ParserPower::UNESCAPE );

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
	 * @return array The function output along with relevant parser options.
	 */
	public static function lstmapRender( Parser $parser, PPFrame $frame, array $params ) {
		$inList = ParserPower::expand( $frame, $params[0] ?? '' );

		if ( $inList === '' ) {
			return [ '', 'noparse' => false ];
		}

		$inSep = ParserPower::expand( $frame, $params[1] ?? ',', ParserPower::UNESCAPE );
		$token = ParserPower::expand( $frame, $params[2] ?? 'x', ParserPower::NO_VARS | ParserPower::UNESCAPE );
		$pattern = $params[3] ?? 'x';
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
	 * @return array The function output along with relevant parser options.
	 */
	public static function lstmaptempRender( Parser $parser, PPFrame $frame, array $params ) {
		$inList = ParserPower::expand( $frame, $params[0] ?? '' );

		if ( $inList === '' ) {
			return [ '', 'noparse' => false ];
		}

		$template = ParserPower::expand( $frame, $params[1] ?? '' );
		$inSep = ParserPower::expand( $frame, $params[2] ?? ',', ParserPower::UNESCAPE );
		$outSep = ParserPower::expand( $frame, $params[3] ?? ',\_', ParserPower::UNESCAPE );
		$sortMode = ParserPower::expand( $frame, $params[4] ?? '' );
		$sortOptions = ParserPower::expand( $frame, $params[5] ?? '' );

		$sortMode = self::decodeSortMode( $sortMode );
		$sortOptions = self::decodeSortOptions( $sortOptions );

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
		$inValue1,
		$inValue2,
		$fieldSep,
		array $tokens1,
		array $tokens2,
		$pattern
	) {
		$inValue1 = trim( $inValue1 );
		$inValue2 = trim( $inValue2 );
		$tokenCount1 = count( $tokens1 );
		$tokenCount2 = count( $tokens2 );

		if ( $inValue1 === '' || $inValue2 === '' ) {
			return;
		}

		$outValue = ParserPower::expand( $frame, $pattern, ParserPower::NO_VARS );
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
		$outValue = $parser->preprocessToDom( $outValue, $frame->isTemplate() ? Parser::PTD_FOR_INCLUSION : 0 );
		return ParserPower::expand( $frame, $outValue, ParserPower::UNESCAPE );
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
	 * @param array $inValues The input values, should be already exploded and fully preprocessed.
	 * @param string $applyFunction Valid name of the function to call for both match and merge processes.
	 * @param array $matchParams Parameter values for the matching process, with open spots for the values.
	 * @param array $mergeParams Parameter values for the merging process, with open spots for the values.
	 * @param int $valueIndex1 The index in $matchParams and $mergeParams where the first value is to go.
	 * @param int $valueIndex2 The index in $matchParams and $mergeParams where the second value is to go.
	 * @return array The function output along with relevant parser options.
	 */
	private static function iterativeListMerge(
		Parser $parser,
		PPFrame $frame,
		array $inValues,
		$applyFunction,
		array $matchParams,
		array $mergeParams,
		$valueIndex1,
		$valueIndex2
	) {
		do {
			$outValues = [];
			$preCount = count( $inValues );

			while ( count( $inValues ) > 0 ) {
				$value1 = $matchParams[$valueIndex1] = $mergeParams[$valueIndex1] = array_shift( $inValues );
				$otherValues = $inValues;
				$inValues = [];

				while ( count( $otherValues ) > 0 ) {
					$value2 = $matchParams[$valueIndex2] = $mergeParams[$valueIndex2] = array_shift( $otherValues );
					$doMerge = call_user_func_array( $applyFunction, $matchParams );
					$doMerge = $parser->replaceVariables( ParserPower::unescape( trim( $doMerge ) ), $frame );
					$doMerge = self::decodeBool( $doMerge );

					if ( $doMerge ) {
						$value1 = call_user_func_array( $applyFunction, $mergeParams );
						$value1 = $parser->replaceVariables( ParserPower::unescape( trim( $value1 ) ), $frame );
						$matchParams[$valueIndex1] = $mergeParams[$valueIndex1] = $value1;
					} else {
						$inValues[] = $value2;
					}
				}

				$outValues[] = $value1;
			}
			$postCount = count( $outValues );
			$inValues = $outValues;
		} while ( $postCount < $preCount && $postCount > 1 );

		return $outValues;
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
	 * @return array The function output along with relevant parser options.
	 */
	private static function mergeListByPattern(
		Parser $parser,
		PPFrame $frame,
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
	) {
		if ( $inList === '' ) {
			return [ $default, 'noparse' => false ];
		}

		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );

		$inValues = self::arrayTrimUnescape( self::explodeList( $inSep, $inList ) );

		if ( $sortMode & self::SORTMODE_PRE ) {
			$inValues = self::sortList( $inValues, $sortOptions );
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
			$outValues = self::sortList( $outValues, $sortOptions );
		}

		if ( count( $outValues ) === 0 ) {
			return [ $default, 'noparse' => false ];
		}

		$outList = implode( $outSep, $outValues );
		$count = strval( count( $outValues ) );
		return [ self::applyIntroAndOutro( $intro, $outList, $outro, $countToken, $count ), 'noparse' => false ];
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
	 * @return array The function output along with relevant parser options.
	 */
	private static function mergeListByTemplate(
		Parser $parser,
		PPFrame $frame,
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
	) {
		if ( $inList === '' ) {
			return [ $default, 'noparse' => false ];
		}

		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );

		$inValues = self::arrayTrimUnescape( self::explodeList( $inSep, $inList ) );

		if ( $sortMode & self::SORTMODE_PRE ) {
			$inValues = self::sortList( $inValues, $sortOptions );
		}

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

		if ( count( $outValues ) === 0 ) {
			return [ $default, 'noparse' => false ];
		}

		$outList = implode( $outSep, $outValues );
		$count = strval( count( $outValues ) );
		return [ self::applyIntroAndOutro( $intro, $outList, $outro, $countToken, $count ), 'noparse' => false ];
	}

	/**
	 * This function renders the listmerge function, sending it to the appropriate processing function based on what
	 * parameter values are provided.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return array The function output along with relevant parser options.
	 */
	public static function listmergeRender( Parser $parser, PPFrame $frame, array $params ) {
		$params = ParserPower::arrangeParams( $frame, $params );

		$inList = ParserPower::expand( $frame, $params["list"] ?? '' );
		$default = ParserPower::expand( $frame, $params["default"] ?? '', ParserPower::UNESCAPE );

		if ( $inList === '' ) {
			return [ $default, 'noparse' => false ];
		}

		$matchTemplate = ParserPower::expand( $frame, $params["matchtemplate"] ?? '' );
		$mergeTemplate = ParserPower::expand( $frame, $params["mergetemplate"] ?? '' );
		$inSep = ParserPower::expand( $frame, $params["insep"] ?? ',', ParserPower::UNESCAPE );
		$fieldSep = ParserPower::expand( $frame, $params["fieldsep"] ?? '', ParserPower::UNESCAPE );
		$token1 = ParserPower::expand( $frame, $params["token1"] ?? '', ParserPower::NO_VARS | ParserPower::UNESCAPE );
		$token2 = ParserPower::expand( $frame, $params["token2"] ?? '', ParserPower::NO_VARS | ParserPower::UNESCAPE );
		$tokenSep = ParserPower::expand( $frame, $params["tokensep"] ?? ',', ParserPower::UNESCAPE );
		$matchPattern = $params["matchpattern"] ?? '';
		$mergePattern = $params["mergepattern"] ?? '';
		$outSep = ParserPower::expand( $frame, $params["outsep"] ?? ',\_', ParserPower::UNESCAPE );
		$sortMode = ParserPower::expand( $frame, $params["sortmode"] ?? '' );
		$sortOptions = ParserPower::expand( $frame, $params["sortoptions"] ?? '' );
		$countToken = ParserPower::expand( $frame, $params["counttoken"] ?? '', ParserPower::NO_VARS | ParserPower::UNESCAPE );
		$intro = ParserPower::expand( $frame, $params["intro"] ?? '', ParserPower::UNESCAPE );
		$outro = ParserPower::expand( $frame, $params["outro"] ?? '', ParserPower::UNESCAPE );

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
