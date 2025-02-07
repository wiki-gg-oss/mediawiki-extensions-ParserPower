<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower;

use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;
use MediaWiki\Parser\Preprocessor;

class ParserPower {
	/**
	 * expand() flag for not expanding variables.
	 */
	public const NO_VARS = 1;
	/**
	 * expand() flag for unescaping after expanding.
	 */
	public const UNESCAPE = 2;
	/**
	 * evaluateUnescaped() flag for evaluating argument syntax in the wikitext.
	 */
	public const WITH_ARGS = 1;

	/**
	 * Sequences to escape, with the replacement character (after a basckslash).
	 */
	private const ESCAPE_CHARS = [
		"\n" => 'n',
		' ' => '_',
		'{' => '{',
		'}' => '}',
		'[' => '(',
		']' => ')',
		'<' => 'l',
		'>' => 'g',
		'=' => 'e',
		'|' => '!',
	];

	/**
	 * Characters to unescape (when used after a backslash), with the replacement sequence.
	 *
	 * Mostly equivalent to array_flip( ParserPower::ESCAPE_CHARS ).
	 */
	private const UNESCAPE_SEQS = [
		'n' => "\n",
		'_' => ' ',
		'{' => '{',
		'}' => '}',
		'(' => '[',
		')' => ']',
		'l' => '<',
		'g' => '>',
		'e' => '=',
		'!' => '|',
		'0' => '',
	];

	/**
	 * This function converts the parameters to the parser function into an array form with all parameter values
	 * trimmed, as per longstanding MediaWiki conventions.
	 *
	 * @param PPFrame $frame The parser frame object.
	 * @param array $unexpandedParams The parameters and values together, not yet exploded or trimmed.
	 * @return array The parameter values associated with the appropriate named or numbered keys
	 */
	public static function arrangeParams( PPFrame $frame, array $unexpandedParams ) {
		$params = [];

		if ( isset( $unexpandedParams[0] ) && is_string( $unexpandedParams[0] ) ) {
			$pair = explode( '=', array_shift( $unexpandedParams ), 2 );
			if ( count( $pair ) === 2 ) {
				$params[trim( $pair[0] )] = trim( $pair[1] );
			} else {
				$params[] = trim( $pair[0] );
			}
		}

		foreach ( $unexpandedParams as $unexpandedParam ) {
			$bits = $unexpandedParam->splitArg();
			if ( $bits['index'] === '' ) {
				$params[ParserPower::expand( $frame, $bits['name'] )] = $bits['value'];
			} else {
				$params[] = $bits['value'];
			}
		}

		return $params;
	}

	/**
	 * The function returns tests a value to see that isn't null or an empty string.
	 *
	 * @param string $value The value to check.
	 * @return bool true for a value that is not null or an empty string.
	 */
	public static function isEmpty( $value ) {
		return $value === null || $value === '';
	}

	/**
	 * Expands and trims a PPNode.
	 *
	 * @param PPFrame $frame
	 * @param PPNode|string $input
	 * @param int $flags
	 * @return string
	 */
	public static function expand( PPFrame $frame, $input, $flags = 0 ) {
		if ( $flags & self::NO_VARS ) {
			$expanded = $frame->expand( $input, PPFrame::NO_ARGS | PPFrame::NO_TEMPLATES );
		} else {
			$expanded = $frame->expand( $input );
		}

		$expanded = trim( $expanded );

		if ( $flags & self::UNESCAPE ) {
			$expanded = self::unescape( $expanded );
		}

		return $expanded;
	}

	/**
	 * Replace variables within a text, that has been unescaped.
	 * In this context, template arguments can not be used.
	 *
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @param string $text
	 * @param int $flags
	 * @return string
	 */
	public static function evaluateUnescaped( Parser $parser, PPFrame $frame, $text, $flags = 0 ) {
		if ( $text === '' ) {
			return '';
		}

		if ( $frame->isTemplate() ) {
			$dom = $parser->preprocessToDom( $text, Preprocessor::DOM_FOR_INCLUSION );
			if ( $flags & ~self::WITH_ARGS ) {
				$frame = $frame->newChild();
			}
		} else {
			$dom = $parser->preprocessToDom( $text );
		}

		return $frame->expand( $dom );
	}

	/**
	 * Replaces all escape sequences with the appropriate characters. It should be calling *after* trimming strings to
	 * protect any leading or trailing whitespace that was escaped.
	 *
	 * @param string $input The string to escape.
	 * @return string The string with all escape sequences replaced.
	 */
	public static function unescape( $input ) {
		$output = '';
		$offset = 0;
		$length = strlen( $input );
		$bsFound = false;
		for ( $i = 0; $i < $length; ++$i ) {
			$char = $input[$i];
			if ( $bsFound ) {
				if ( $char === '\\' ) {
					// Double backslash
					$output .= substr( $input, $offset, $i - $offset );
					$offset = $i + 1;
				} elseif ( isset( self::UNESCAPE_SEQS[$char] ) ) {
					// Escape sequence
					$output .= substr( $input, $offset, $i - $offset - 1 );
					$output .= self::UNESCAPE_SEQS[$char];
					$offset = $i + 1;
				}
				$bsFound = false;
			} elseif ( $char === '\\' ) {
				// Backslash
				// The next char will tell us what to do.
				$bsFound = true;
			}
		}

		if ( $offset > 0 ) {
			$output .= substr( $input, $offset );
			return $output;
		} else {
			return $input;
		}
	}

	/**
	 * Replaces all appropriate characters with escape sequences.
	 *
	 * @param string $input The string to escape.
	 * @return string The escaped string.
	 */
	public static function escape( $input ) {
		$output = '';
		$offset = 0;
		$length = strlen( $input );
		$bsFound = false;
		for ( $i = 0; $i < $length; ++$i ) {
			$char = $input[$i];
			if ( $bsFound ) {
				if ( $char === '\\' ) {
					// Double backslash
					$output .= '\\\\';
					$offset = $i + 1;
				} elseif ( isset( self::UNESCAPE_SEQS[$char] ) ) {
					// Escape sequence
					$output .= $char;
					$offset = $i + 1;
				} elseif ( isset( self::ESCAPE_CHARS[$char] ) ) {
					// Backslash followed by a character replaceable with an escape sequence
					$output .= '\\' . self::ESCAPE_CHARS[$char];
					$offset = $i + 1;
				}
				$bsFound = false;
			} else {
				if ( $char === '\\' ) {
					// Always escape a backslash, whatever the next char (if any),
					// because we do not want wikitext concatenations to generate spurious escape sequences.
					$output .= substr( $input, $offset, $i - $offset );
					$output .= '\\\\';
					$offset = $i + 1;
					$bsFound = true;
				} elseif ( isset( self::ESCAPE_CHARS[$char] ) ) {
					// Character replaceable with an escape sequence
					$output .= substr( $input, $offset, $i - $offset );
					$output .= '\\' . self::ESCAPE_CHARS[$char];
					$offset = $i + 1;
				}
			}
		}

		if ( $offset > 0 ) {
			$output .= substr( $input, $offset );
			return $output;
		} else {
			return $input;
		}
	}

	/**
	 * Replaces the indicated token in the pattern with the input value.
	 *
	 * @param string $inValue The value to change into one or more template parameters.
	 * @param string $token The token to replace.
	 * @param string $pattern Pattern containing token to be replaced with the input value.
	 * @return string The result of the token replacement within the pattern.
	 */
	public static function applyPattern( $inValue, $token, $pattern ) {
		return self::applyPatternWithIndex( $inValue, '', 0, $token, $pattern );
	}

	/**
	 * Replaces the indicated index token in the pattern with the given index and the token in the
	 * pattern with the input value.
	 *
	 * @param string $inValue The value to change into one or more template parameters.
	 * @param string $indexToken The token to replace with the index, or null/empty value to skip index replacement.
	 * @param int $index The numeric index of this value.
	 * @param string $token The token to replace.
	 * @param string $pattern Pattern containing token to be replaced with the input value.
	 * @return string The result of the token replacement within the pattern.
	 */
	public static function applyPatternWithIndex( $inValue, $indexToken, $index, $token, $pattern ) {
		if ( $pattern === '' ) {
			return $inValue;
		}

		$outValue = $pattern;
		if ( $indexToken !== null && $indexToken !== '' ) {
			$outValue = str_replace( $indexToken, strval( $index ), $outValue );
		}
		if ( $token !== null && $token !== '' ) {
			$outValue = str_replace( $token, $inValue, $outValue );
		}

		return $outValue;
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
	public static function applyFieldPattern( $inValue, $fieldSep, array $tokens, $tokenCount, $pattern ) {
		return self::applyFieldPatternWithIndex( $inValue, $fieldSep, '', 0, $tokens, $tokenCount, $pattern );
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
	public static function applyFieldPatternWithIndex(
		$inValue,
		$fieldSep,
		$indexToken,
		$index,
		array $tokens,
		$tokenCount,
		$pattern
	) {
		if ( $pattern === '' ) {
			return $inValue;
		}

		$outValue = $pattern;
		if ( $indexToken !== null && $indexToken !== '' ) {
			$outValue = str_replace( $indexToken, strval( $index ), $outValue );
		}
		$fields = explode( $fieldSep, $inValue, $tokenCount );
		$fieldCount = count( $fields );
		for ( $i = 0; $i < $tokenCount; $i++ ) {
			$outValue = str_replace( $tokens[$i], ( $i < $fieldCount ) ? $fields[$i] : '', $outValue );
		}

		return $outValue;
	}
}
