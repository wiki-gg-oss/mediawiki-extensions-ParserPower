<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower;

use MediaWiki\Message\Message;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;
use MediaWiki\Parser\PPNode;
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
		's' => ' ',
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
	 * Expands and trims a PPNode.
	 *
	 * @param PPFrame $frame
	 * @param PPNode|string $input
	 * @param int $flags
	 * @return string
	 */
	public static function expand( PPFrame $frame, PPNode|string $input, int $flags = 0 ): string {
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
	public static function evaluateUnescaped( Parser $parser, PPFrame $frame, string $text, int $flags = 0 ): string {
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
	public static function unescape( string $input ): string {
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
	public static function escape( string $input ): string {
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
	 * @param string $value The value to change into one or more template parameters.
	 * @param string $token The token to replace.
	 * @param string $pattern Pattern containing token to be replaced with the input value.
	 * @return string The result of the token replacement within the pattern.
	 */
	public static function applyPattern( string $value, string $token, string $pattern ): string {
		if ( $pattern === '' ) {
			return $value;
		} else {
			return str_replace( $token, $value, $pattern );
		}
	}

	/**
	 * Create a ParserPower-specific message.
	 * 
	 * @param string $key Message key.
	 * @param mixed ...$params Message parameters.
	 * @return Message The ParserPower message.
	 */
	public static function newMessage( string $key, ...$params ): Message {
		return wfMessage( 'parserpower-' . $key, ...$params );
	}

	/**
	 * Returns a ParserPower error message formatted as wikitext (with variables replaced).
	 *
	 * @param string $key Error message key, without its "error-" prefix.
	 * @param mixed ...$params Error message parameters.
	 * @return string The formatted message text.
	 */
	public static function errorMessage( string $key, ...$params ): string {
		$message = ParserPower::newMessage( 'error-' . $key, ...$params );
		return '<strong class="error">' . $message->inContentLanguage()->text() . '</strong>';
	}
}
