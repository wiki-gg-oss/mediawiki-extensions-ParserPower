<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower;

use Countable;
use MediaWiki\Extension\ParserPower\Operation\ListInclusionOperation;
use MediaWiki\Extension\ParserPower\Operation\PatternOperation;
use MediaWiki\Extension\ParserPower\Operation\TemplateOperation;
use MediaWiki\Extension\ParserPower\Operation\WikitextOperation;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;
use StringUtils;

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

	private const PARAM_OPTIONS = [
		'counttoken' => [ 'unescape' => true ],
		'default' => [ 'unescape' => true ],
		'duplicates' => [],
		'fieldsep' => [ 'unescape' => true ],
		'keep' => [],
		'keepcs' => [],
		'keepsep' => [ 'default' => ',' ],
		'indextoken' => [ 'unescape' => true ],
		'insep' => [ 'unescape' => true, 'default' => ',' ],
		'intro' => [ 'unescape' => true ],
		'list' => [],
		'outro' => [ 'unescape' => true ],
		'outsep' => [ 'unescape' => true, 'default' => ', ' ],
		'matchpattern' => [],
		'matchtemplate' => [],
		'mergepattern' => [],
		'mergetemplate' => [],
		'pattern' => [],
		'remove' => [],
		'removecs' => [],
		'removesep' => [ 'default' => ',' ],
		'removecs' => [],
		'sortoptions' => [],
		'subsort' => [],
		'subsortoptions' => [],
		'template' => [],
		'token' => [ 'unescape' => true ],
		'token1' => [ 'unescape' => true ],
		'token2' => [ 'unescape' => true ],
		'tokensep' => [ 'unescape' => true, 'default' => ',' ],
		'uniquecs' => [],
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
	 * Split a list value into an array of fields by a given delimiter.
	 *
	 * @param string $sep Delimiter used to separate the fields.
	 * @param string $value Value to split.
	 * @param ?int $fieldLimit Maximum number of fields, null if there is no upper bound.
	 * @return array The fields, in an array of strings.
	 */
	private static function explodeValue( string $sep, string $value, ?int $fieldLimit = null ): array {
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
	private static function explodeToken( string $sep, string $token ): array {
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

	public function __construct(
		private readonly bool $useLegacyLstmapExpansion
	) { }

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
	 * This function performs the filtering operation for the listfilter function.
	 *
	 * @param WikitextOperation $operation Operation to apply.
	 * @param array $inValues Array with the input values.
	 * @param string $fieldSep Separator between fields, if any.
	 * @return array The array stripped of any values with non-unique keys.
	 */
	private static function filterList( WikitextOperation $operation, array $inValues, string $fieldSep = '' ): array {
		$fieldLimit = $operation->getFieldLimit();

		$outValues = [];
		foreach ( $inValues as $i => $inValue ) {
			$result = $operation->apply( self::explodeValue( $fieldSep, $inValue, $fieldLimit ), $i + 1 );
			if ( strtolower( $result ) !== 'remove' ) {
				$outValues[] = $inValue;
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
		$params = new ParameterArranger( $frame, $params, self::PARAM_OPTIONS );

		$inList = $params->get( 'list' );
		$default = $params->get( 'default' );

		$keepValues = $params->get( 'keep' );
		$keepSep = $params->get( 'keepsep' );
		$keepCS = $params->get( 'keepcs' );
		$removeValues = $params->get( 'remove' );
		$removeSep = $params->get( 'removesep' );
		$removeCS = $params->get( 'removecs' );
		$template = $params->get( 'template' );
		$inSep = $params->get( 'insep' );
		$fieldSep = $params->get( 'fieldsep' );
		$indexToken = $params->get( 'indextoken' );
		$token = $params->get( 'token' );
		$tokenSep = $params->get( 'tokensep' );
		$pattern = $params->get( 'pattern' );
		$outSep = $params->get( 'outsep' );
		$countToken = $params->get( 'counttoken' );
		$intro = $params->get( 'intro' );
		$outro = $params->get( 'outro' );

		if ( $inList === '' ) {
			return ParserPower::evaluateUnescaped( $parser, $frame, $default );
		}

		$keepCS = self::decodeBool( $keepCS );
		$removeCS = self::decodeBool( $removeCS );

		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );
		$tokenSep = $parser->getStripState()->unstripNoWiki( $tokenSep );

		$inValues = self::explodeList( $inSep, $inList );

		if ( $keepValues !== '' ) {
			if ( $keepSep !== '' ) {
				$keepValues = self::explodeList( $keepSep, $keepValues );
			} else {
				$keepValues = [ ParserPower::unescape( $keepValues ) ];
			}

			$operation = new ListInclusionOperation( $keepValues, '', 'remove', $keepCS );
		} elseif ( $removeValues !== '' ) {
			if ( $removeSep !== '' ) {
				$removeValues = self::explodeList( $removeSep, $removeValues );
			} else {
				$removeValues = [ ParserPower::unescape( $removeValues ) ];
			}

			$operation = new ListInclusionOperation( $removeValues, 'remove', '', $removeCS );
		} elseif ( $template !== '' ) {
			$operation = new TemplateOperation( $parser, $frame, $template );
		} else {
			if ( $fieldSep !== '' ) {
				$tokens = self::explodeToken( $tokenSep, $token );
			} else {
				$tokens = [ $token ];
			}

			$operation = new PatternOperation( $parser, $frame, $pattern, $tokens, $indexToken );
		}

		$outValues = self::filterList( $operation, $inValues, $fieldSep );

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

		if ( $valueSep !== '' ) {
			$values = self::explodeList( $valueSep, $values );
		} else {
			$values = [ ParserPower::unescape( $values ) ];
		}

		$operation = new ListInclusionOperation( $values, '', 'remove', $csOption );
		$outValues = self::filterList( $operation, $inValues );

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

		$value = ParserPower::expand( $frame, $params[0] ?? '', ParserPower::UNESCAPE );
		$inSep = ParserPower::expand( $frame, $params[2] ?? ',', ParserPower::UNESCAPE );
		$outSep = ParserPower::expand( $frame, $params[3] ?? ',\_', ParserPower::UNESCAPE );
		$csOption = ParserPower::expand( $frame, $params[4] ?? '' );

		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );
		$csOption = self::decodeCSOption( $csOption );

		$inValues = self::explodeList( $inSep, $inList );

		$operation = new ListInclusionOperation( [ $value ], 'remove', '', $csOption );
		$outValues = self::filterList( $operation, $inValues );

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
	 * This function performs the reduction to unique values operation for the listunique function.
	 *
	 * @param WikitextOperation $operation Operation to apply.
	 * @param array $inValues Array with the input values.
	 * @param string $fieldSep Separator between fields, if any.
	 * @return array The array stripped of any values with non-unique keys.
	 */
	private static function reduceToUniqueValuesByKey(
		WikitextOperation $operation,
		array $inValues,
		string $fieldSep = ''
	): array {
		$fieldLimit = $operation->getFieldLimit();

		$previousKeys = [];
		$outValues = [];
		foreach ( $inValues as $i => $value ) {
			$key = $operation->apply( self::explodeValue( $fieldSep, $value, $fieldLimit ), $i + 1 );
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
	public function listuniqueRender( Parser $parser, PPFrame $frame, array $params ): string {
		$params = new ParameterArranger( $frame, $params, self::PARAM_OPTIONS );

		$inList = $params->get( 'list' );
		$default = $params->get( 'default' );

		$uniqueCS = $params->get( 'uniquecs' );
		$template = $params->get( 'template' );
		$inSep = $params->get( 'insep' );
		$fieldSep = $params->get( 'fieldsep' );
		$indexToken = $params->get( 'indextoken' );
		$token = $params->get( 'token' );
		$tokenSep = $params->get( 'tokensep' );
		$pattern = $params->get( 'pattern' );
		$outSep = $params->get( 'outsep' );
		$countToken = $params->get( 'counttoken' );
		$intro = $params->get( 'intro' );
		$outro = $params->get( 'outro' );

		if ( $inList === '' ) {
			return ParserPower::evaluateUnescaped( $parser, $frame, $default );
		}

		$uniqueCS = self::decodeBool( $uniqueCS );

		$inValues = self::explodeList( $inSep, $inList );

		if ( $fieldSep !== '' ) {
			$tokens = self::explodeToken( $tokenSep, $token );
		} else {
			$tokens = [ $token ];
		}

		if ( $template !== '' ) {
			$operation = new TemplateOperation( $parser, $frame, $template );
			$outValues = self::reduceToUniqueValuesByKey( $operation, $inValues, $fieldSep );
		} elseif ( ( $indexToken !== '' || $token !== '' ) && $pattern !== '' ) {
			$operation = new PatternOperation( $parser, $frame, $pattern, $tokens, $indexToken );
			$outValues = self::reduceToUniqueValuesByKey( $operation, $inValues, $fieldSep );
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
	 * Generates the sort keys. This returns an array of the values where each element is an array with the sort key
	 * in element 0 and the value in element 1.
	 *
	 * @param WikitextOperation $operation Operation to apply.
	 * @param array $values Array with the input values.
	 * @param string $fieldSep Separator between fields, if any.
	 * @return array An array where each value has been paired with a sort key in a two-element array.
	 */
	private static function generateSortKeys( WikitextOperation $operation, array $values, string $fieldSep = '' ): array {
		$fieldLimit = $operation->getFieldLimit();

		$pairedValues = [];
		foreach ( $values as $i => $value ) {
			$key = $operation->apply( self::explodeValue( $fieldSep, $value, $fieldLimit ), $i + 1 );
			$pairedValues[] = [ $key, $value ];
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
	 * This function directs the sort operation for the listsort function.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return string The function output.
	 */
	public function listsortRender( Parser $parser, PPFrame $frame, array $params ): string {
		$params = new ParameterArranger( $frame, $params, self::PARAM_OPTIONS );

		$inList = $params->get( 'list' );
		$default = $params->get( 'default' );

		$template = $params->get( 'template' );
		$inSep = $params->get( 'insep' );
		$fieldSep = $params->get( 'fieldsep' );
		$indexToken = $params->get( 'indextoken' );
		$token = $params->get( 'token' );
		$tokenSep = $params->get( 'tokensep' );
		$pattern = $params->get( 'pattern' );
		$outSep = $params->get( 'outsep' );
		$sortOptions = $params->get( 'sortoptions' );
		$subsort = $params->get( 'subsort' );
		$subsortOptions = $params->get( 'subsortoptions' );
		$duplicates = $params->get( 'duplicates' );
		$countToken = $params->get( 'counttoken' );
		$intro = $params->get( 'intro' );
		$outro = $params->get( 'outro' );

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

		if ( $template !== '' ) {
			$sortOptions = self::decodeSortOptions( $sortOptions, ListSorter::NUMERIC );
			$sorter = new ListSorter( $sortOptions, $subsortOptions );
			$operation = new TemplateOperation( $parser, $frame, $template );

			$pairedValues = self::generateSortKeys( $operation, $values, $fieldSep );
			$sorter->sortPairs( $pairedValues );
			$values = self::discardSortKeys( $pairedValues );
		} elseif ( ( $indexToken !== '' || $token !== '' ) && $pattern !== '' ) {
			if ( $fieldSep !== '' ) {
				$tokens = self::explodeToken( $tokenSep, $token );
			} else {
				$tokens = [ $token ];
			}

			$sortOptions = self::decodeSortOptions( $sortOptions, ListSorter::NUMERIC );
			$sorter = new ListSorter( $sortOptions, $subsortOptions );
			$operation = new PatternOperation( $parser, $frame, $pattern, $tokens, $indexToken );

			$pairedValues = self::generateSortKeys( $operation, $values, $fieldSep );
			$sorter->sortPairs( $pairedValues );
			$values = self::discardSortKeys( $pairedValues );
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
	 * This function performs the value changing operation for the listmap function.
	 *
	 * @param WikitextOperation $operation Operation to apply.
	 * @param bool $keepEmpty True to keep empty values once the operation applied, false to remove empty values.
	 * @param array $inValues Array with the input values.
	 * @param string $fieldSep Separator between fields, if any.
	 * @return array The function output.
	 */
	private static function mapList(
		WikitextOperation $operation,
		bool $keepEmpty,
		array $inValues,
		string $fieldSep = ''
	): array {
		$fieldLimit = $operation->getFieldLimit();

		$outValues = [];
		foreach ( $inValues as $i => $inValue ) {
			$outValue = $operation->apply( self::explodeValue( $fieldSep, $inValue, $fieldLimit ), $i + 1 );
			if ( $outValue !== '' || $keepEmpty ) {
				$outValues[] = $outValue;
			}
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
	public function listmapRender( Parser $parser, PPFrame $frame, array $params ): string {
		$params = new ParameterArranger( $frame, $params, self::PARAM_OPTIONS );

		$inList = $params->get( 'list' );
		$default = $params->get( 'default' );

		$template = $params->get( 'template' );
		$inSep = $params->get( 'insep' );
		$fieldSep = $params->get( 'fieldsep' );
		$indexToken = $params->get( 'indextoken' );
		$token = $params->get( 'token' );
		$tokenSep = $params->get( 'tokensep' );
		$pattern = $params->get( 'pattern' );
		$outSep = $params->get( 'outsep' );
		$sortMode = $params->get( 'sortmode' );
		$sortOptions = $params->get( 'sortoptions' );
		$duplicates = $params->get( 'duplicates' );
		$countToken = $params->get( 'counttoken' );
		$intro = $params->get( 'intro' );
		$outro = $params->get( 'outro' );

		if ( $inList === '' ) {
			return ParserPower::evaluateUnescaped( $parser, $frame, $default );
		}

		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );
		$sortMode = self::decodeSortMode( $sortMode );
		$sortOptions = self::decodeSortOptions( $sortOptions );
		$duplicates = self::decodeDuplicates( $duplicates );

		$sorter = new ListSorter( $sortOptions );

		$inValues = self::explodeList( $inSep, $inList );

		if ( $duplicates & self::DUPLICATES_PRESTRIP ) {
			$inValues = array_unique( $inValues );
		}

		if ( $template !== '' ) {
			if ( $sortMode & self::SORTMODE_PRE ) {
				$inValues = $sorter->sort( $inValues );
			}

			$operation = new TemplateOperation( $parser, $frame, $template );
			$outValues = self::mapList( $operation, true, $inValues, $fieldSep );

			if ( $sortMode & ( self::SORTMODE_POST | self::SORTMODE_COMPAT ) ) {
				$outValues = $sorter->sort( $outValues );
			}
		} else {
			if ( ( $indexToken !== '' && $sortMode & self::SORTMODE_COMPAT ) || $sortMode & self::SORTMODE_PRE ) {
				$inValues = $sorter->sort( $inValues );
			}

			if ( $fieldSep !== '' ) {
				$tokens = self::explodeToken( $tokenSep, $token );
			} else {
				$tokens = [ $token ];
			}

			$operation = new PatternOperation( $parser, $frame, $pattern, $tokens, $indexToken );
			$outValues = self::mapList( $operation, false, $inValues, $fieldSep );

			if ( ( $indexToken === '' && $sortMode & self::SORTMODE_COMPAT ) || $sortMode & self::SORTMODE_POST ) {
				$outValues = $sorter->sort( $outValues );
			}
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
		$legacyExpansionFlags = $this->useLegacyLstmapExpansion ? ParserPower::NO_VARS : 0;
		$token = ParserPower::expand( $frame, $params[2] ?? 'x', $legacyExpansionFlags | ParserPower::UNESCAPE );
		$pattern = ParserPower::expand( $frame, $params[3] ?? 'x', $legacyExpansionFlags );
		$outSep = ParserPower::expand( $frame, $params[4] ?? ',\_', ParserPower::UNESCAPE );
		$sortMode = ParserPower::expand( $frame, $params[5] ?? '' );
		$sortOptions = ParserPower::expand( $frame, $params[6] ?? '' );

		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );
		$sortMode = self::decodeSortMode( $sortMode );
		$sortOptions = self::decodeSortOptions( $sortOptions );

		$sorter = new ListSorter( $sortOptions );

		$inValues = self::explodeList( $inSep, $inList );

		if ( $sortMode & self::SORTMODE_PRE ) {
			$inValues = $sorter->sort( $inValues );
		}

		$operation = new PatternOperation( $parser, $frame, $pattern, [ $token ] );
		$outValues = self::mapList( $operation, false, $inValues, '' );

		if ( $sortMode & ( self::SORTMODE_COMPAT | self::SORTMODE_POST ) ) {
			$outValues = $sorter->sort( $outValues );
		}

		if ( count( $outValues ) === 0 ) {
			return '';
		}

		return ParserPower::evaluateUnescaped( $parser, $frame, implode( $outSep, $outValues ) );
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

		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );
		$sortMode = self::decodeSortMode( $sortMode );
		$sortOptions = self::decodeSortOptions( $sortOptions );

		$sorter = new ListSorter( $sortOptions );

		$inValues = self::explodeList( $inSep, $inList );

		if ( $sortMode & self::SORTMODE_PRE ) {
			$inValues = $sorter->sort( $inValues );
		}

		$operation = new TemplateOperation( $parser, $frame, $template );
		$outValues = self::mapList( $operation, true, $inValues, '' );

		if ( $sortMode & ( self::SORTMODE_POST | self::SORTMODE_COMPAT ) ) {
			$outValues = $sorter->sort( $outValues );
		}

		if ( count( $outValues ) === 0 ) {
			return '';
		}

		return ParserPower::evaluateUnescaped( $parser, $frame, implode( $outSep, $outValues ) );
	}

	/**
	 * This function performs repeated merge passes until either the input array is merged to a single value, or until
	 * a merge pass is completed that does not perform any further merges (pre- and post-pass array count is the same).
	 * Each merge pass operates by performing a conditional on all possible pairings of items, immediately merging two
	 * if the conditional indicates it should and reducing the possible pairings. The logic for the conditional and
	 * the actual merge process is supplied through a user-defined function.
	 *
	 * @param WikitextOperation $matchOperation Operation to apply for the matching process.
	 * @param WikitextOperation $mergeOperation Operation to apply for the merging process.
	 * @param array $values Array with the input values.
	 * @param string $fieldSep Separator between fields, if any.
	 * @param ?int $fieldOffset Number of fields that the first value should cover.
	 * @return array An array with the output values.
	 */
	private static function iterativeListMerge(
		WikitextOperation $matchOperation,
		WikitextOperation $mergeOperation,
		array $values,
		string $fieldSep = '',
		?int $fieldOffset = null
	): array {
		$checkedPairs = [];

		do {
			$preCount = $count = count( $values );

			for ( $i1 = 0; $i1 < $count; ++$i1 ) {
				$value1 = $values[$i1];
				$shift = 0;

				for ( $i2 = $i1 + 1; $i2 < $count; ++$i2 ) {
					$value2 = $values[$i2];
					unset( $values[$i2] );

					$fields1 = self::explodeValue( $fieldSep, $value1, $fieldOffset );
					$offset = $fieldOffset ?? count( $fields1 );

					if ( isset( $checkedPairs[$value1][$value2] ) ) {
						$doMerge = $checkedPairs[$value1][$value2];
					} else {
						$fieldLimit = $matchOperation->getFieldLimit();
						if ( $fieldLimit !== null ) {
							$fieldLimit = $fieldLimit - $offset;
						}

						$fields = $fields1;
						foreach ( self::explodeValue( $fieldSep, $value2, $fieldLimit ) as $i => $field ) {
							$fields[$offset + $i] = $field;
						}

						$doMerge = $matchOperation->apply( $fields );
						$doMerge = self::decodeBool( $doMerge );
						$checkedPairs[$value1][$value2] = $doMerge;
					}

					if ( $doMerge ) {
						$fieldLimit = $mergeOperation->getFieldLimit();
						if ( $fieldLimit !== null ) {
							$fieldLimit = $fieldLimit - $offset;
						}

						$fields = $fields1;
						foreach ( self::explodeValue( $fieldSep, $value2, $fieldLimit ) as $i => $field ) {
							$fields[$offset + $i] = $field;
						}

						$value1 = $mergeOperation->apply( $fields );
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
	 * This function renders the listmerge function, sending it to the appropriate processing function based on what
	 * parameter values are provided.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return string The function output.
	 */
	public function listmergeRender( Parser $parser, PPFrame $frame, array $params ): string {
		$params = new ParameterArranger( $frame, $params, self::PARAM_OPTIONS );

		$inList = $params->get( 'list' );
		$default = $params->get( 'default' );

		$matchTemplate = $params->get( 'matchtemplate' );
		$mergeTemplate = $params->get( 'mergetemplate' );
		$inSep = $params->get( 'insep' );
		$fieldSep = $params->get( 'fieldsep' );
		$token1 = $params->get( 'token1' );
		$token2 = $params->get( 'token2' );
		$tokenSep = $params->get( 'tokensep' );
		$matchPattern = $params->get( 'matchpattern' );
		$mergePattern = $params->get( 'mergepattern' );
		$outSep = $params->get( 'outsep' );
		$sortMode = $params->get( 'sortmode' );
		$sortOptions = $params->get( 'sortoptions' );
		$countToken = $params->get( 'counttoken' );
		$intro = $params->get( 'intro' );
		$outro = $params->get( 'outro' );

		if ( $inList === '' ) {
			return ParserPower::evaluateUnescaped( $parser, $frame, $default );
		}

		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );
		$sortMode = self::decodeSortMode( $sortMode );
		$sortOptions = self::decodeSortOptions( $sortOptions );

		$sorter = new ListSorter( $sortOptions );

		$inValues = self::explodeList( $inSep, $inList );

		if ( $sortMode & self::SORTMODE_PRE ) {
			$inValues = $sorter->sort( $inValues );
		}

		if ( $matchTemplate !== '' && $mergeTemplate !== '' ) {
			$matchOperation = new TemplateOperation( $parser, $frame, $matchTemplate );
			$mergeOperation = new TemplateOperation( $parser, $frame, $mergeTemplate );
		} else {
			if ( $fieldSep !== '' ) {
				$tokens1 = self::explodeToken( $tokenSep, $token1 );
				$tokens2 = self::explodeToken( $tokenSep, $token2 );
			} else {
				$tokens1 = [ $token1 ];
				$tokens2 = [ $token2 ];
			}
			$tokens = [ ...$tokens1, ...$tokens2 ];
			$fieldOffset = count( $tokens1 );

			$matchOperation = new PatternOperation( $parser, $frame, $matchPattern, $tokens );
			$mergeOperation = new PatternOperation( $parser, $frame, $mergePattern, $tokens );

		}

		$outValues = self::iterativeListMerge( $matchOperation, $mergeOperation, $inValues, $fieldSep, $fieldOffset ?? null );

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
}
