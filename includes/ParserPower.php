<?php
/**
 * Main Class
 *
 * @package   ParserPower
 * @author    Eyes <eyes@aeongarden.com>, Samuel Hilson <shilson@fandom.com>
 * @copyright Copyright � 2013 Eyes
 * @copyright 2019 Wikia Inc.
 * @license   GPL-2.0-or-later
 */

namespace MediaWiki\Extension\ParserPower;

use Parser;
use PPFrame;

class ParserPower {
	/**
	 * This function converts the parameters to the parser function into an array form with all parameter values
	 * trimmed, as per longstanding MediaWiki conventions.
	 *
	 * @param PPFrame $frame The parser frame object.
	 * @param array $unexpandedParams The parameters and values together, not yet exploded or trimmed.
	 * @return array The parameter values associated with the appropriate named or numbered keys
	 */
	public static function arrangeParams( $frame, $unexpandedParams ) {
		$params = [];
		foreach ( $unexpandedParams as $unexpandedParam ) {
			$param = explode( '=', $frame->expand( $unexpandedParam ), 2 );
			if ( count( $param ) == 2 ) {
				$params[trim( $param[0] )] = trim( $param[1] );
			} else {
				$params[] = trim( $param[0] );
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
	 * Replaces all escape sequences with the appropriate characters. It should be calling *after* trimming strings to
	 * protect any leading or trailing whitespace that was escaped.
	 *
	 * @param string $input The string to escape.
	 * @return string The string with all escape sequences replaced.
	 */
	public static function unescape( $input ) {
		$output = '';
		$length = strlen( $input );
		for ( $i = 0; $i < $length; ++$i ) {
			$char = substr( $input, $i, 1 );

			if ( $char !== "\\" ) {
				$output .= $char;
				continue;
			}

			$sequence = substr( $input, $i, 2 );
			switch ( $sequence ) {
				case "\\n":
					$output .= "\n";
					break;
				case "\\_":
					$output .= " ";
					break;
				case "\\\\":
					$output .= "\\";
					break;
				case "\\{":
					$output .= "{";
					break;
				case "\\}":
					$output .= "}";
					break;
				case "\\(":
					$output .= "[";
					break;
				case "\\)":
					$output .= "]";
					break;
				case "\\l":
					$output .= "<";
					break;
				case "\\g":
					$output .= ">";
					break;
				case "\\e":
					$output .= "=";
					break;
				case "\\!":
					$output .= "|";
					break;
				case "\\0":
					$output .= "";
					break;
				default:
					$output .= $sequence;
					break;
			}
			$i += 1;
		}

		return $output;
	}

	/**
	 * Replaces all appropriate characters with escape sequences.
	 *
	 * @param string $input The string to escape.
	 * @return string The escaped string.
	 */
	public static function escape( $input ) {
		$output = '';
		$length = strlen( $input );
		for ( $i = 0; $i < $length; ++$i ) {
			$char = substr( $input, $i, 1 );
			switch ( $char ) {
				case "\\":
					$sequence = substr( $input, $i, 2 );
					switch ( $sequence ) {
						case "\\n":
							$output .= "\\\\n";
							$i += 1;
							break;
						case "\\_":
							$output .= "\\\\_";
							$i += 1;
							break;
						case "\\\\":
							$output .= "\\\\\\\\";
							$i += 1;
							break;
						case "\\{":
							$output .= "\\\\{";
							$i += 1;
							break;
						case "\\}":
							$output .= "\\\\}";
							$i += 1;
							break;
						case "\\(":
							$output .= "\\\\(";
							$i += 1;
							break;
						case "\\)":
							$output .= "\\\\)";
							$i += 1;
							break;
						case "\\l":
							$output .= "\\\\l";
							$i += 1;
							break;
						case "\\g":
							$output .= "\\\\g";
							$i += 1;
							break;
						case "\\e":
							$output .= "\\\\e";
							$i += 1;
							break;
						case "\\!":
							$output .= "\\\\!";
							$i += 1;
							break;
						case "\\0":
							$output .= "\\\\0";
							$i += 1;
							break;
						default:
							$output .= "\\\\";
							break;
					}
					break;
				case "\n":
					$output .= "\\n";
					break;
				case " ":
					$output .= "\\_";
					break;
				case "{":
					$output .= "\\{";
					break;
				case "}":
					$output .= "\\}";
					break;
				case "[":
					$output .= "\\(";
					break;
				case "]":
					$output .= "\\)";
					break;
				case "<":
					$output .= "\\l";
					break;
				case ">":
					$output .= "\\g";
					break;
				case "=":
					$output .= "\\e";
					break;
				case "|":
					$output .= "\\!";
					break;
				default:
					$output .= $char;
					break;
			}
		}

		return $output;
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
	public static function applyPattern( $parser, $frame, $inValue, $token, $pattern ) {
		return self::applyPatternWithIndex( $parser, $frame, $inValue, '', 0, $token, $pattern );
	}

	/**
	 * Replaces the indicated index token in the pattern with the given index and the token in the
	 * pattern with the input value.
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
	public static function applyPatternWithIndex( $parser, $frame, $inValue, $indexToken, $index, $token, $pattern ) {
		$inValue = trim( $inValue );
		if ( trim( $pattern ) !== '' ) {
			$outValue = $frame->expand( $pattern, PPFrame::NO_ARGS || PPFrame::NO_TEMPLATES );
			if ( $indexToken !== null && $indexToken !== '' ) {
				$outValue = str_replace( $indexToken, strval( $index ), $outValue );
			}
			if ( $token !== null && $token !== '' ) {
				$outValue = str_replace( $token, $inValue, $outValue );
			}
		} else {
			$outValue = $inValue;
		}
		$outValue = $parser->preprocessToDom( $outValue, $frame->isTemplate() ? Parser::PTD_FOR_INCLUSION : 0 );
		return self::unescape( trim( $frame->expand( $outValue ) ) );
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
	public static function applyFieldPattern( $parser, $frame, $inValue, $fieldSep, $tokens, $tokenCount, $pattern ) {
		return self::applyFieldPatternWithIndex(
			$parser,
			$frame,
			$inValue,
			$fieldSep,
			'',
			0,
			$tokens,
			$tokenCount,
			$pattern
		);
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
	public static function applyFieldPatternWithIndex(
		$parser,
		$frame,
		$inValue,
		$fieldSep,
		$indexToken,
		$index,
		$tokens,
		$tokenCount,
		$pattern
	) {
		$inValue = trim( $inValue );
		if ( trim( $pattern ) !== '' ) {
			$outValue = $frame->expand( $pattern, PPFrame::NO_ARGS || PPFrame::NO_TEMPLATES );
			if ( $indexToken !== null && $indexToken !== '' ) {
				$outValue = str_replace( $indexToken, strval( $index ), $outValue );
			}
			$fields = explode( $fieldSep, $inValue, $tokenCount );
			$fieldCount = count( $fields );
			for ( $i = 0; $i < $tokenCount; $i++ ) {
				$outValue = str_replace( $tokens[$i], ( $i < $fieldCount ) ? $fields[$i] : '', $outValue );
			}
		} else {
			$outValue = $inValue;
		}
		$outValue = $parser->preprocessToDom( $outValue, $frame->isTemplate() ? Parser::PTD_FOR_INCLUSION : 0 );
		return self::unescape( trim( $frame->expand( $outValue ) ) );
	}
}
