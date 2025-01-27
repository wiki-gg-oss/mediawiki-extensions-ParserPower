<?php
/**
 * Simple Class
 *
 * @package   ParserPower
 * @author    Eyes <eyes@aeongarden.com>, Samuel Hilson <shilson@fandom.com>
 * @copyright Copyright � 2013 Eyes
 * @copyright 2019 Wikia Inc.
 * @license   GPL-2.0-or-later
 */

namespace MediaWiki\Extension\ParserPower;

use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use Parser;
use PPFrame;
use PPNode_Hash_Array;

final class SimpleFunctions {
	/**
	 * Registers the simple, generic parser functions with the parser.
	 *
	 * @param Parser &$parser The parser object being initialized.
	 * @return void
	 */
	public static function setup( &$parser ) {
		$parser->setFunctionHook( 'trim', [ __CLASS__, 'trimRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'uesc', [ __CLASS__, 'uescRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'uescnowiki', [ __CLASS__, 'uescnowikiRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'trimuesc', [ __CLASS__, 'trimuescRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setHook( 'linkpage', [ __CLASS__, 'linkpageRender' ] );
		$parser->setHook( 'linktext', [ __CLASS__, 'linktextRender' ] );
		$parser->setHook( 'esc', [ __CLASS__, 'escRender' ] );
		for ( $i = 1; $i < 10; ++$i ) {
			$parser->setHook( 'esc' . $i, [ __CLASS__, 'escRender' ] );
		}
		$parser->setFunctionHook( 'ueif', [ __CLASS__, 'ueifRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'or', [ __CLASS__, 'orRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'ueor', [ __CLASS__, 'ueorRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'ueifeq', [ __CLASS__, 'ueifeqRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'token', [ __CLASS__, 'tokenRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'tokenif', [ __CLASS__, 'tokenifRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'ueswitch', [ __CLASS__, 'ueswitchRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'follow', [ __CLASS__, 'followRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'argmap', [ __CLASS__, 'argmapRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'iargmap', [ __CLASS__, 'iargmapRender' ], Parser::SFH_OBJECT_ARGS );

		// Do not load if Page Forms is installed.
		if ( !defined( 'PF_VERSION' ) ) {
			$parser->setFunctionHook( 'arraymap', [ __CLASS__, 'arraymapRender' ], Parser::SFH_OBJECT_ARGS );
			$parser->setFunctionHook( 'arraymaptemplate', [ __CLASS__, 'arraymaptemplateRender' ],
				Parser::SFH_OBJECT_ARGS );
		}
	}

	/**
	 * This function performs the trim operation for the trim parser function.
	 *
	 * @param Parser $parser The parser object. Ignored.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return array The function output along with relevant parser options.
	 */
	public static function trimRender( Parser $parser, PPFrame $frame, array $params ) {
		$text = ParserPower::expand( $frame, $params[0] ?? '' );

		return [ $text, 'noparse' => false ];
	}

	/**
	 * This function performs the unescape operation for the uesc parser function. This trims the value first, leaving
	 * whitespace intact if it's there after escape sequences are replaced.
	 *
	 * @param Parser $parser The parser object. Ignored.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return array The function output along with relevant parser options.
	 */
	public static function uescRender( Parser $parser, PPFrame $frame, array $params ) {
		$text = ParserPower::expand( $frame, $params[0] ?? '', ParserPower::UNESCAPE );

		return [ $text, 'noparse' => false ];
	}

	/**
	 * This function performs the unescape operation for the uescnowiki parser function. This trims the value first,
	 * leaving whitespace intact if it's there after escape sequences are replaced. It returns the content wrapped in
	 * <nowiki> tags so that it isn't parsed.
	 *
	 * @param Parser $parser The parser object. Ignored.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return array The function output along with relevant parser options.
	 */
	public static function uescnowikiRender( Parser $parser, PPFrame $frame, array $params ) {
		$text = ParserPower::expand( $frame, $params[0] ?? '', ParserPower::UNESCAPE );

		return [ '<nowiki>' . $text . '</nowiki>', 'noparse' => false ];
	}

	/**
	 * This function performs the unescape operation for the trimuesc parser function. This trims the value after
	 * replacement, so any leading or trailing whitespace is trimmed no matter how it got there.
	 *
	 * @param Parser $parser The parser object. Ignored.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return array The function output along with relevant parser options.
	 */
	public static function trimuescRender( Parser $parser, PPFrame $frame, array $params ) {
		$text = ParserPower::expand( $frame, $params[0] ?? '', ParserPower::UNESCAPE );

		return [ trim( $text ), 'noparse' => false ];
	}

	/**
	 * This function performs the delinking operation for the linktext parser function.
	 * This removes internal links from, the given wikicode, replacing them with
	 * the name of the page they would have linked to.
	 *
	 * @param string $text The text within the tag function.
	 * @param array $attribs Attributes values of the tag function. Ignored.
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @return array The function output along with relevant parser options.
	 */
	public static function linkpageRender( $text, array $attribs, Parser $parser, PPFrame $frame ) {
		$text = $parser->replaceVariables( $text, $frame );

		if ( $text ) {
			$text = preg_replace_callback( '/\[\[(.*?)\]\]/', [ __CLASS__, 'linkpageReplace' ], $text );
		}

		return [ $text, 'markerType' => 'none' ];
	}

	/**
	 * This function replaces the links found by linkpageRender and replaces them with the
	 * name of the page they link to.
	 *
	 * @param array $matches The parameters and values together, not yet exploded or trimmed.
	 * @return string The function output along with relevant parser options.
	 */
	public static function linkpageReplace( $matches ) {
		$parts = explode( '|', $matches[1], 2 );
		return $parts[0];
	}

	/**
	 * This function performs the delinking operation for the linktext parser function.
	 * This removes internal links from, the given wikicode, replacing them with
	 * the text that any links would return.
	 *
	 * @param string $text The text within the tag function.
	 * @param array $attribs Attributes values of the tag function. Ignored.
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @return array The function output along with relevant parser options.
	 */
	public static function linktextRender( $text, array $attribs, Parser $parser, PPFrame $frame ) {
		$text = $parser->replaceVariables( $text, $frame );

		if ( $text !== '' ) {
			$text = preg_replace_callback( '/\[\[(.*?)\]\]/', [ __CLASS__, 'linktextReplace' ], $text );
		}

		return [ $text, 'markerType' => 'none' ];
	}

	/**
	 * This function replaces the links found by linktextRender and replaces them with their appropriate link text.
	 *
	 * @param array $matches The parameters and values together, not yet exploded or trimmed.
	 * @return array The function output along with relevant parser options.
	 */
	public static function linktextReplace( $matches ) {
		$parts = explode( '|', $matches[1], 2 );
		if ( count( $parts ) == 2 ) {
			return $parts[1];
		} else {
			return $parts[0];
		}
	}

	/**
	 * This function escapes all appropriate characters in the given text and returns the result.
	 *
	 * @param string $text The text within the tag function.
	 * @param array $attribs Attributes values of the tag function. Ignored.
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @return array The function output along with relevant parser options.
	 */
	public static function escRender( $text, array $attribs, Parser $parser, PPFrame $frame ) {
		$text = ParserPower::escape( $text );

		$text = $parser->replaceVariables( $text, $frame );

		return [ $text, 'markerType' => 'none' ];
	}

	/**
	 * This function performs the test for the ueif function.
	 *
	 * @param Parser $parser The parser object. Ignored.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return array The function output along with relevant parser options.
	 */
	public static function ueifRender( Parser $parser, PPFrame $frame, array $params ) {
		$condition = ParserPower::expand( $frame, $params[0] ?? '' );

		if ( $condition !== '' ) {
			$value = ParserPower::expand( $frame, $params[1] ?? '', ParserPower::UNESCAPE );
		} else {
			$value = ParserPower::expand( $frame, $params[2] ?? '', ParserPower::UNESCAPE );
		}

		return [ $value, 'noparse' => false ];
	}

	/**
	 * This function performs the test for the or function.
	 *
	 * @param Parser $parser The parser object. Ignored.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return array The function output along with relevant parser options.
	 */
	public static function orRender( Parser $parser, PPFrame $frame, array $params ) {
		foreach ( $params as $param ) {
			$inValue = ParserPower::expand( $frame, $param );

			if ( $inValue !== '' ) {
				return [ $inValue, 'noparse' => false ];
			}
		}

		return [ '', 'noparse' => false ];
	}

	/**
	 * This function performs the test for the ueor function.
	 *
	 * @param Parser $parser The parser object. Ignored.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return array The function output along with relevant parser options.
	 */
	public static function ueorRender( Parser $parser, PPFrame $frame, array $params ) {
		foreach ( $params as $param ) {
			$inValue = ParserPower::expand( $frame, $param );

			if ( $inValue !== '' ) {
				return [ ParserPower::unescape( $inValue ), 'noparse' => false ];
			}
		}

		return [ '', 'noparse' => false ];
	}

	/**
	 * This function performs the test for the ueifeq function.
	 *
	 * @param Parser $parser The parser object. Ignored.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return array The function output along with relevant parser options.
	 */
	public static function ueifeqRender( Parser $parser, PPFrame $frame, array $params ) {
		$leftValue = ParserPower::expand( $frame, $params[0] ?? '', ParserPower::UNESCAPE );
		$rightValue = ParserPower::expand( $frame, $params[1] ?? '', ParserPower::UNESCAPE );

		if ( $leftValue === $rightValue ) {
			$value = ParserPower::expand( $frame, $params[2] ?? '', ParserPower::UNESCAPE );
		} else {
			$value = ParserPower::expand( $frame, $params[3] ?? '', ParserPower::UNESCAPE );
		}

		return [ $value, 'noparse' => false ];
	}

	/**
	 * This function performs the replacement for the token function.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return array The function output along with relevant parser options.
	 */
	public static function tokenRender( Parser $parser, PPFrame $frame, array $params ) {
		$inValue = ParserPower::expand( $frame, $params[0] ?? '' );

		$token = ParserPower::expand( $frame, $params[1] ?? 'x', ParserPower::UNESCAPE );
		$pattern = $params[2] ?? 'x';

		return [ ParserPower::applyPattern( $parser, $frame, $inValue, $token, $pattern ), 'noparse' => false ];
	}

	/**
	 * This function performs the replacement for the tokenif function.
	 *
	 * @param Parser $parser The parser object.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return array The function output along with relevant parser options.
	 */
	public static function tokenifRender( Parser $parser, PPFrame $frame, array $params ) {
		$inValue = ParserPower::expand( $frame, $params[0] ?? '' );

		if ( $inValue === '' ) {
			$default = ParserPower::expand( $frame, $params[3] ?? '', ParserPower::UNESCAPE );

			return [ $default, 'noparse' => false ];
		}

		$token = ParserPower::expand( $frame, $params[1] ?? 'x', ParserPower::UNESCAPE );
		$pattern = $params[2] ?? 'x';

		return [ ParserPower::applyPattern( $parser, $frame, $inValue, $token, $pattern ), 'noparse' => false ];
	}

	/**
	 * This function performs the test for the ueswitch function.
	 *
	 * @param Parser $parser The parser object. Ignored.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return array The function output along with relevant parser options.
	 */
	public static function ueswitchRender( Parser $parser, PPFrame $frame, array $params ) {
		$switchKey = isset( $params[0] ) ? ParserPower::expand( $frame, array_shift( $params ), ParserPower::UNESCAPE ) : '';

		if ( count( $params ) === 0 ) {
			return [ '', 'noparse' => false ];
		}

		$lastBits = $params[count( $params ) - 1]->splitArg();
		$frame->expand( $lastBits['name'] );
		$lastValue = $frame->expand( $lastBits['value'] );

		$default = '';
		$mwDefaultFound = false;
		$mwDefault = $parser->getMagicWordFactory()->get( 'default' );

		$keyFound = false;
		foreach ( $params as $param ) {
			$bits = $param->splitArg();
			if ( $bits['index'] === '' ) {
				$key = ParserPower::expand( $frame, $bits['name'] );
				$value = ParserPower::expand( $frame, $bits['value'] );
			} else {
				$key = ParserPower::expand( $frame, $bits['value'] );
				$value = null;
			}

			if ( !$keyFound ) {
				$key = ParserPower::unescape( $key );
				if ( $key === $switchKey ) {
					$keyFound = true;
				} elseif ( $mwDefault->matchStartToEnd( $key ) ) {
					$mwDefaultFound = true;
				}
			}

			if ( $value !== null ) {
				if ( $keyFound ) {
					return [ ParserPower::unescape( $value ), 'noparse' => false ];
				} elseif ( $mwDefaultFound ) {
					$default = $value;
					$mwDefaultFound = false;
				}
			}
		}

		if ( $lastBits['index'] !== '' ) {
			$default = $lastValue;
		}
		return [ ParserPower::expand( $frame, $default, ParserPower::UNESCAPE ), 'noparse' => false ];
	}

	/**
	 * This function performs the follow operation for the follow parser function.
	 *
	 * @param Parser $parser The parser object. Ignored.
	 * @param PPFrame $frame The parser frame object.
	 * @param array $params The parameters and values together, not yet expanded or trimmed.
	 * @return array The function output along with relevant parser options.
	 */
	public static function followRender( Parser $parser, PPFrame $frame, array $params ) {
		$text = trim( ParserPower::expand( $frame, $params[0] ?? '', ParserPower::UNESCAPE ) );

		$title = Title::newFromText( $text );
		if ( $title === null || $title->getNamespace() === NS_MEDIA || $title->getNamespace() < 0 ) {
			return $text;
		}

		$page = MediaWikiServices::getInstance()->getWikiPageFactory()->newFromTitle( $title );
		$target = $page->getRedirectTarget();
		if ( $target === null ) {
			return $text;
		}

		// Replace redirect fragment with the one from the initial text. We need to check whether there is
		// a # with no fragment after it, since it removes the redirect fragment if there is one.
		if ( strpos( $text, '#' ) !== false ) {
			$target = $target->createFragmentTarget( $title->getFragment() );
		}

		return $target->getFullText();
	}

	public static function arraymapRender( $parser, $frame, $args ) {
		// Set variables.
		$value = isset( $args[0] ) ? trim( $frame->expand( $args[0] ) ) : '';
		$delimiter = isset( $args[1] ) ? trim( $frame->expand( $args[1] ) ) : ',';
		$var = isset( $args[2] ) ? trim( $frame->expand( $args[2], PPFrame::NO_ARGS | PPFrame::NO_TEMPLATES ) ) : 'x';
		$formula = isset( $args[3] ) ? $args[3] : 'x';
		$new_delimiter = isset( $args[4] ) ? trim( $frame->expand( $args[4] ) ) : ', ';
		$conjunction = isset( $args[5] ) ? trim( $frame->expand( $args[5] ) ) : $new_delimiter;
		// Unstrip some.
		$delimiter = $parser->getStripState()->unstripNoWiki( $delimiter );
		// Let '\n' represent newlines, and '\s' represent spaces.
		$delimiter = str_replace( [ '\n', '\s' ], [ "\n", ' ' ], $delimiter );
		$new_delimiter = str_replace( [ '\n', '\s' ], [ "\n", ' ' ], $new_delimiter );
		$conjunction = str_replace( [ '\n', '\s' ], [ "\n", ' ' ], $conjunction );

		if ( $delimiter == '' ) {
			$values_array = preg_split( '/(.)/u', $value, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE );
		} else {
			$values_array = explode( $delimiter, $value );
		}

		$results_array = [];
		// Add results to the results array only if the old value was
		// non-null, and the new, mapped value is non-null as well.
		foreach ( $values_array as $old_value ) {
			$old_value = trim( $old_value );
			if ( $old_value == '' ) {
				continue;
			}
			$result_value = $frame->expand( $formula, PPFrame::NO_ARGS | PPFrame::NO_TEMPLATES );
			$result_value = str_replace( $var, $old_value, $result_value );
			$result_value = $parser->preprocessToDom( $result_value, $frame->isTemplate() ? Parser::PTD_FOR_INCLUSION : 0 );
			$result_value = trim( $frame->expand( $result_value ) );
			if ( $result_value == '' ) {
				continue;
			}
			$results_array[] = $result_value;
		}
		if ( $conjunction != $new_delimiter ) {
			$conjunction = " " . trim( $conjunction ) . " ";
		}

		$result_text = "";
		$num_values = count( $results_array );
		for ( $i = 0; $i < $num_values; $i++ ) {
			if ( $i == 0 ) {
				$result_text .= $results_array[$i];
			} elseif ( $i == $num_values - 1 ) {
				$result_text .= $conjunction . $results_array[$i];
			} else {
				$result_text .= $new_delimiter . $results_array[$i];
			}
		}
		return $result_text;
	}

	public static function arraymaptemplateRender( $parser, $frame, $args ) {
		// Set variables.
		$value = isset( $args[0] ) ? trim( $frame->expand( $args[0] ) ) : '';
		$template = isset( $args[1] ) ? trim( $frame->expand( $args[1] ) ) : '';
		$delimiter = isset( $args[2] ) ? trim( $frame->expand( $args[2] ) ) : ',';
		$new_delimiter = isset( $args[3] ) ? trim( $frame->expand( $args[3] ) ) : ', ';
		// Unstrip some.
		$delimiter = $parser->getStripState()->unstripNoWiki( $delimiter );
		// let '\n' represent newlines
		$delimiter = str_replace( '\n', "\n", $delimiter );
		$new_delimiter = str_replace( '\n', "\n", $new_delimiter );

		if ( $delimiter == '' ) {
			$values_array = preg_split( '/(.)/u', $value, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE );
		} else {
			$values_array = explode( $delimiter, $value );
		}

		$results_array = [];
		foreach ( $values_array as $old_value ) {
			$old_value = trim( $old_value );
			if ( $old_value == '' ) {
				continue;
			}
			$bracketed_value = $frame->virtualBracketedImplode( '{{', '|', '}}',
				$template, '1=' . $old_value );
			// Special handling if preprocessor class is set to
			// 'Preprocessor_Hash'.
			if ( $bracketed_value instanceof PPNode_Hash_Array ) {
				$bracketed_value = $bracketed_value->value;
			}
			$results_array[] = $parser->replaceVariables( implode( '', $bracketed_value ), $frame );
		}
		return implode( $new_delimiter, $results_array );
	}

	public static function argmapRender( Parser $parser, PPFrame $frame, array $args ) {
		if ( !isset( $args[0] ) ) {
			return [ '', 'noparse' => false ];
		}

		// set parameters
		$formatter = isset( $args[1] ) ? trim( $frame->expand( $args[0] ) ) : '';
		$glue = isset( $args[1] ) ? trim( $frame->expand( $args[1] ) ) : '';
		$mustContainString = isset( $args[2] ) ? trim( $frame->expand( $args[2] ) ) : '';
		$onlyShowString = isset( $args[3] ) ? trim( $frame->expand( $args[3] ) ) : '';
		$formatterArgs = $frame->getNamedArguments();

		// make arrays
		$mustContain = [];
		$onlyShow = [];
		if ( $mustContainString !== '' ) {
			$mustContain = explode( ',', $mustContainString );
		}
		if ( $onlyShowString !== '' ) {
			$onlyShow = explode( ',', $onlyShowString );
		}

		// group formatter arguments to groupedFormatterArgs array, if viable
		$groupedFormatterArgs = [];
		foreach ( $formatterArgs as $key => $arg ) {
			$index = preg_replace( '/[^0-9]/', '', $key );
			$argName = preg_replace( '/[^a-zA-Z]/', '', $key );

			if ( $index !== '' ) {
				$index = intval( $index );
				if ( !isset( $groupedFormatterArgs[$index] ) ) {
					$groupedFormatterArgs[$index] = [];
				}
				$groupedFormatterArgs[$index][$argName] = $arg;
			}
		}

		// write formatter calls, if viable
		$formatterCalls = [];
		foreach ( $groupedFormatterArgs as $formatterArg ) {
			// check if there are missing arguments
			$missingArgs = array_diff( $mustContain, array_keys( $formatterArg ) );
			if ( !empty( $missingArgs ) ) {
				continue;
			}

			// process individual args and filter for onlyShow
			$processedFormatterArg = [];
			foreach ( $formatterArg as $key => $value ) {
				if ( empty( $onlyShow ) || in_array( $key, $onlyShow ) ) {
					$processedFormatterArg[] = "$key=$value";
				}
			}

			// discard if nothing remains
			if ( empty( $processedFormatterArg ) ) {
				continue;
			}

			// construct final formatter call
			$val = implode( '|', $processedFormatterArg );
			$formatterCall = $frame->virtualBracketedImplode( '{{', '|', '}}', $formatter, $val );
			if ( $formatterCall instanceof PPNode_Hash_Array ) {
				$formatterCall = $formatterCall->value;
			}
			$formatterCall = implode( '', $formatterCall );

			// parse formatter call
			$formatterCalls[] = trim( $parser->replaceVariables( $formatterCall, $frame ) );
		}

		// proper '\n' handling
		$glue = str_replace( '\n', "\n", $glue );
		return implode( $glue, $formatterCalls );
	}

	public static function iargmapRender( Parser $parser, PPFrame $frame, array $args ) {
		if ( !isset( $args[0] ) ) {
			return [ '<strong class="error">iargmap error: The parameter "formatter" is required.</strong>', 'noparse' => false ];
		}
		if ( !isset( $args[1] ) ) {
			return [ '<strong class="error">iargmap error: The parameter "n" is required.</strong>', 'noparse' => false ];
		}

		// set parameters
		$formatter = trim( $frame->expand( $args[0] ) );
		$numberOfArgumentsPerFormatter = trim( $frame->expand( $args[1] ) );
		$glue = isset( $args[2] ) ? trim( $frame->expand( $args[2] ) ) : '';
		$allFormatterArgs = $frame->getNumberedArguments();
		
		// check against bad entries
		if ( count( $allFormatterArgs ) == 0 ) {
			return [ '<strong class="error">iargmap error: No formatter arguments were given.</strong>', 'noparse' => false ];
		}
		if ( !is_numeric( $numberOfArgumentsPerFormatter ) ) {
			return [ '<strong class="error">iargmap error: "n" must be an integer.</strong>', 'noparse' => false ];
		}

		if (  intval( $numberOfArgumentsPerFormatter ) != floatval( $numberOfArgumentsPerFormatter ) ) {
			return [ '<strong class="error">iargmap error: "n" must be an integer.</strong>', 'noparse' => false ];
		}

		$imax = count( $allFormatterArgs ) / intval( $numberOfArgumentsPerFormatter );

		if ( !is_int( $imax ) ) {
			return [ '<strong class="error">iargmap error: The number of given formatter arguments must be divisible by "n".</strong>', 'noparse' => false ];
		}

		// write formatter calls
		$formatterCalls = [];
		for ( $i = 0; $i < $imax; $i++) { 
			$formatterArgs = [];
			for ( $n = 0; $n < $numberOfArgumentsPerFormatter; $n++) {
				$formatterArgs[] = trim( $frame->expand( $allFormatterArgs[ $i * $numberOfArgumentsPerFormatter + $n + 1] ) );
			}

			$val = implode( '|', $formatterArgs );
			$formatterCall = $frame->virtualBracketedImplode( '{{', '|', '}}', $formatter, $val );
			if ( $formatterCall instanceof PPNode_Hash_Array ) {
				$formatterCall = $formatterCall->value;
			}
			$formatterCall = implode( '', $formatterCall );

			// parse formatter call
			$formatterCalls[] = trim( $parser->replaceVariables( $formatterCall, $frame ) );
		}

		// proper '\n' handling
		$glue = str_replace( '\n', "\n", $glue );
		return implode( $glue, $formatterCalls );
	}
}
