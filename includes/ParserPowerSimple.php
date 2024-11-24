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

namespace ParserPower;

use MediaWiki\MediaWikiServices;
use PPFrame;
use Title;
use Parser;
use PPNode_Hash_Array;

class ParserPowerSimple {
	/**
	 * Registers the simple, generic parser functions with the parser.
	 *
	 * @param Parser $parser The parser object being initialized.
	 *
	 * @return void
	 */
	public static function setup(&$parser) {
		$parser->setFunctionHook(
			'trim',
			'ParserPower\\ParserPowerSimple::trimRender',
			Parser::SFH_OBJECT_ARGS
		);
		$parser->setFunctionHook(
			'uesc',
			'ParserPower\\ParserPowerSimple::uescRender',
			Parser::SFH_OBJECT_ARGS
		);
		$parser->setFunctionHook(
			'uescnowiki',
			'ParserPower\\ParserPowerSimple::uescnowikiRender',
			Parser::SFH_OBJECT_ARGS
		);
		$parser->setFunctionHook(
			'trimuesc',
			'ParserPower\\ParserPowerSimple::trimuescRender',
			Parser::SFH_OBJECT_ARGS
		);
		$parser->setHook(
			'linkpage',
			'ParserPower\\ParserPowerSimple::linkpageRender'
		);
		$parser->setHook(
			'linktext',
			'ParserPower\\ParserPowerSimple::linktextRender'
		);
		$parser->setHook(
			'esc',
			'ParserPower\\ParserPowerSimple::escRender'
		);
		for ($i = 1; $i < 10; ++$i) {
			$parser->setHook(
				'esc' . $i,
				'ParserPower\\ParserPowerSimple::escRender'
			);
		}
		$parser->setFunctionHook(
			'ueif',
			'ParserPower\\ParserPowerSimple::ueifRender',
			Parser::SFH_OBJECT_ARGS
		);
		$parser->setFunctionHook(
			'or',
			'ParserPower\\ParserPowerSimple::orRender',
			Parser::SFH_OBJECT_ARGS
		);
		$parser->setFunctionHook(
			'ueor',
			'ParserPower\\ParserPowerSimple::ueorRender',
			Parser::SFH_OBJECT_ARGS
		);
		$parser->setFunctionHook(
			'ueifeq',
			'ParserPower\\ParserPowerSimple::ueifeqRender',
			Parser::SFH_OBJECT_ARGS
		);
		$parser->setFunctionHook(
			'token',
			'ParserPower\\ParserPowerSimple::tokenRender',
			Parser::SFH_OBJECT_ARGS
		);
		$parser->setFunctionHook(
			'tokenif',
			'ParserPower\\ParserPowerSimple::tokenifRender',
			Parser::SFH_OBJECT_ARGS
		);
		$parser->setFunctionHook(
			'ueswitch',
			'ParserPower\\ParserPowerSimple::ueswitchRender',
			Parser::SFH_OBJECT_ARGS
		);
		$parser->setFunctionHook(
			'follow',
			'ParserPower\\ParserPowerSimple::followRender',
			Parser::SFH_OBJECT_ARGS
		);
		
		if ( defined( 'PF_VERSION' ) ) {
			// Do not load if Page Forms is installed.
			return;
		}
		
		$parser->setFunctionHook( 'arraymap', 'ParserPower\\ParserPowerSimple::arraymapRender',
			Parser::SFH_OBJECT_ARGS );

		$parser->setFunctionHook( 'arraymaptemplate', 'ParserPower\\ParserPowerSimple::arraymaptemplateRender',
			Parser::SFH_OBJECT_ARGS );
		
	}

	/**
	 * This function performs the trim operation for the trim parser function.
	 *
	 * @param Parser  $parser The parser object. Ignored.
	 * @param PPFrame $frame  The parser frame object.
	 * @param array   $params The parameters and values together, not yet expanded or trimmed.
	 *
	 * @return array The function output along with relevant parser options.
	 */
	public static function trimRender($parser, $frame, $params) {
		return [ isset($params[0]) ? trim($frame->expand($params[0])) : '',
			'noparse' => false
		];
	}

	/**
	 * This function performs the unescape operation for the uesc parser function. This trims the value first, leaving
	 * whitespace intact if it's there after escape sequences are replaced.
	 *
	 * @param Parser  $parser The parser object.
	 * @param PPFrame $frame  The parser frame object.
	 * @param array   $params Attributes values of the tag function.
	 *
	 * @return array The function output along with relevant parser options.
	 */
	public static function uescRender($parser, $frame, $params) {
		return [ isset($params[0]) ? ParserPower::unescape(trim($frame->expand($params[0]))) : '',
			'noparse' => false
		];
	}

	/**
	 * This function performs the unescape operation for the uescnowiki parser function. This trims the value first,
	 * leaving whitespace intact if it's there after escape sequences are replaced. It returns the content wrapped in
	 * <nowiki> tags so that it isn't parsed.
	 *
	 * @param Parser  $parser The parser object.
	 * @param PPFrame $frame  The parser frame object.
	 * @param array   $params Attributes values of the tag function.
	 *
	 * @return array The function output along with relevant parser options.
	 */
	public static function uescnowikiRender($parser, $frame, $params) {
		$text = isset($params[0]) ? ParserPower::unescape(trim($frame->expand($params[0]))) : '';

		return [ '<nowiki>' . $text . '</nowiki>',
			'noparse' => false
		];
	}

	/**
	 * This function performs the unescape operation for the trimuesc parser function. This trims the value after
	 * replacement, so any leading or trailing whitespace is trimmed no matter how it got there.
	 *
	 * @param Parser  $parser The parser object. Ignored.
	 * @param PPFrame $frame  The parser frame object.
	 * @param array   $params The parameters and values together, not yet expanded or trimmed.
	 *
	 * @return array The function output along with relevant parser options.
	 */
	public static function trimuescRender($parser, $frame, $params) {
		return [ isset($params[0]) ? trim(ParserPower::unescape($frame->expand($params[0]))) : '',
			'noparse' => false
		];
	}

	/**
	 * This function performs the delinking operation for the linktext parser function.
	 * This removes internal links from, the given wikicode, replacing them with
	 * the name of the page they would have linked to.
	 *
	 * @param Parser  $parser  The parser object.
	 * @param PPFrame $frame   The parser frame object.
	 * @param string  $text    The text within the tag function.
	 * @param array   $attribs Attributes values of the tag function.
	 *
	 * @return array The function output along with relevant parser options.
	 */
	public static function linkpageRender($text, $attribs, $parser, PPFrame $frame) {
		$text = $parser->replaceVariables($text, $frame);

		if ($text) {
			return [ preg_replace_callback('/\[\[(.*?)\]\]/', 'self::linkpageReplace', $text),
				'noparse' => false,
				'markerType' => 'none'
			];
		} else {
			return ['', 'markerType' => 'none' ];
		}
	}

	/**
	 * This function replaces the links found by linkpageRender and replaces them with the
	 * name of the page they link to.
	 *
	 * @param array $matches The parameters and values together, not yet exploded or trimmed.
	 *
	 * @return array The function output along with relevant parser options.
	 */
	public static function linkpageReplace($matches) {
		$parts = explode('|', $matches[1], 2);
		return $parts[0];
	}

	/**
	 * This function performs the delinking operation for the linktext parser function.
	 * This removes internal links from, the given wikicode, replacing them with
	 * the text that any links would return.
	 *
	 * @param Parser  $parser  The parser object. Ignored.
	 * @param PPFrame $frame   The parser frame object.
	 * @param string  $text    The parameters and values together, not yet exploded or trimmed.
	 * @param array   $attribs
	 *
	 * @return array The function output along with relevant parser options.
	 */
	public static function linktextRender($text, $attribs, $parser, PPFrame $frame) {
		$text = $parser->replaceVariables($text, $frame);

		if ($text) {
			return [ preg_replace_callback('/\[\[(.*?)\]\]/', 'self::linktextReplace', $text),
				'noparse' => false,
				'markerType' => 'none'
			];
		} else {
			return [ '', 'markerType' => 'none' ];
		}
	}

	/**
	 * This function replaces the links found by linktextRender and replaces them with their appropriate link text.
	 *
	 * @param array $matches The parameters and values together, not yet exploded or trimmed.
	 *
	 * @return array The function output along with relevant parser options.
	 */
	public static function linktextReplace($matches) {
		$parts = explode('|', $matches[1], 2);
		if (count($parts) == 2) {
			return $parts[1];
		} else {
			return $parts[0];
		}
	}

	/**
	 * This function escapes all appropriate characters in the given text and returns the result.
	 *
	 * @param Parser  $parser  The parser object.
	 * @param PPFrame $frame   The parser frame object.
	 * @param string  $text    The text within the tag function.
	 * @param array   $attribs Attributes values of the tag function.
	 *
	 * @return array The function output along with relevant parser options.
	 */
	public static function escRender($text, $attribs, $parser, $frame) {
		$text = ParserPower::escape($text);

		$text = $parser->replaceVariables($text, $frame);

		return [ $text, 'noparse' => false, 'markerType' => 'none' ];
	}

	/**
	 * This function performs the test for the ueif function.
	 *
	 * @param Parser  $parser The parser object.
	 * @param PPFrame $frame  The parser frame object.
	 * @param array   $params The parameters and values together, not yet expanded or trimmed.
	 *
	 * @return array The function output along with relevant parser options.
	 */
	public static function ueifRender($parser, $frame, $params) {
		$condition = isset($params[0]) ? trim($frame->expand($params[0])) : '';
		$trueValue = isset($params[1]) ? $params[1] : '';
		$falseValue = isset($params[2]) ? $params[2] : '';

		if ($condition !== '') {
			return [ParserPower::unescape($frame->expand($trueValue)), 'noparse' => false];
		} else {
			return [ParserPower::unescape($frame->expand($falseValue)), 'noparse' => false];
		}
	}

	/**
	 * This function performs the test for the or function.
	 *
	 * @param Parser  $parser The parser object.
	 * @param PPFrame $frame  The parser frame object.
	 * @param array   $params The parameters and values together, not yet expanded or trimmed.
	 *
	 * @return array The function output along with relevant parser options.
	 */
	public static function orRender($parser, $frame, $params) {
		foreach ($params as $param) {
			$inValue = trim($frame->expand($param));

			if ($inValue !== '') {
				return [$inValue, 'noparse' => false];
			}
		}

		return ['', 'noparse' => false];
	}

	/**
	 * This function performs the test for the ueor function.
	 *
	 * @param Parser  $parser The parser object. Ignored.
	 * @param PPFrame $frame  The parser frame object.
	 * @param array   $params The parameters and values together, not yet expanded or trimmed.
	 *
	 * @return array The function output along with relevant parser options.
	 */
	public static function ueorRender($parser, $frame, $params) {

		foreach ($params as $param) {
			$inValue = trim($frame->expand($param));

			if ($inValue !== '') {
				return [ParserPower::unescape($inValue), 'noparse' => false];
			}
		}

		return ['', 'noparse' => false];
	}

	/**
	 * This function performs the test for the ueifeq function.
	 *
	 * @param Parser  $parser The parser object.
	 * @param PPFrame $frame  The parser frame object.
	 * @param array   $params The parameters and values together, not yet expanded or trimmed.
	 *
	 * @return array The function output along with relevant parser options.
	 */
	public static function ueifeqRender($parser, $frame, $params) {
		$leftValue = isset($params[0]) ? ParserPower::unescape(trim($frame->expand($params[0]))) : '';
		$rightValue = isset($params[1]) ? ParserPower::unescape(trim($frame->expand($params[1]))) : '';
		$trueValue = isset($params[2]) ? $params[2] : '';
		$falseValue = isset($params[3]) ? $params[3] : '';

		if ($leftValue === $rightValue) {
			return [ParserPower::unescape($frame->expand($trueValue)), 'noparse' => false];
		} else {
			return [ParserPower::unescape($frame->expand($falseValue)), 'noparse' => false];
		}
	}

	/**
	 * This function performs the replacement for the token function.
	 *
	 * @param Parser  $parser The parser object.
	 * @param PPFrame $frame  The parser frame object.
	 * @param array   $params The parameters and values together, not yet expanded or trimmed.
	 *
	 * @return array The function output along with relevant parser options.
	 */
	public static function tokenRender($parser, $frame, $params) {
		$inValue = isset($params[0]) ? trim($frame->expand($params[0])) : '';

		$token = isset($params[1]) ?
			ParserPower::unescape(trim($frame->expand($params[1], PPFrame::NO_ARGS | PPFrame::NO_TEMPLATES))) : 'x';
		$pattern = isset($params[2]) ? $params[2] : 'x';

		return [ParserPower::applyPattern($parser, $frame, $inValue, $token, $pattern), 'noparse' => false];
	}

	/**
	 * This function performs the replacement for the tokenif function.
	 *
	 * @param Parser  $parser The parser object.
	 * @param PPFrame $frame  The parser frame object.
	 * @param array   $params The parameters and values together, not yet expanded or trimmed.
	 *
	 * @return array The function output along with relevant parser options.
	 */
	public static function tokenifRender($parser, $frame, $params) {
		$inValue = isset($params[0]) ? trim($frame->expand($params[0])) : '';
		$default = isset($params[3]) ? trim($frame->expand($params[3])) : '';

		if ($inValue !== '') {
			$token = isset($params[1]) ?
				ParserPower::unescape(trim($frame->expand($params[1], PPFrame::NO_ARGS | PPFrame::NO_TEMPLATES))) :
				'x';
			$pattern = isset($params[2]) ? $params[2] : 'x';

			return [ParserPower::applyPattern($parser, $frame, $inValue, $token, $pattern), 'noparse' => false];
		} else {
			return [ParserPower::unescape($default), 'noparse' => false];
		}
	}

	/**
	 * This function performs the test for the ueswitch function.
	 *
	 * @param Parser  $parser The parser object.
	 * @param PPFrame $frame  The parser frame object.
	 * @param array   $params The parameters and values together, not yet expanded or trimmed.
	 *
	 * @return array The function output along with relevant parser options.
	 */
	public static function ueswitchRender($parser, $frame, $params) {
		$switchKey = isset($params[0]) ? ParserPower::unescape(trim($frame->expand(array_shift($params)))) : '';

		if (count($params) > 0) {
			$lastItem = $frame->expand($params[count($params) - 1]);
			$default = '';
			$mwDefaultFound = false;
			$mwDefault = $parser->getMagicWordFactory()->get('default');

			$keyFound = false;
			foreach ($params as $param) {
				$pair = explode('=', trim($frame->expand($param)), 2);

				if (!$keyFound) {
					$key = ParserPower::unescape($pair[0]);
					if ($key === $switchKey) {
						$keyFound = true;
					} else if ($mwDefault->matchStartToEnd($key)) {
						$mwDefaultFound = true;
					}
				}

				if (count($pair) > 1) {
					if ($keyFound) {
						return [ParserPower::unescape(trim($frame->expand($pair[1]))), 'noparse' => false];
					} else if ($mwDefaultFound) {
						$default = $pair[1];
						$mwDefaultFound = false;
					}
				}
			}

			if (strpos($lastItem, '=') === false) {
				$default = $lastItem;
			}
			return [ParserPower::unescape(trim($default)), 'noparse' => false];
		} else {
			return ['', 'noparse' => false];
		}
	}

	/**
	 * This function performs the follow operation for the follow parser function.
	 *
	 * @param Parser  $parser The parser object. Ignored.
	 * @param PPFrame $frame  The parser frame object.
	 * @param array   $params The parameters and values together, not yet expanded or trimmed.
	 *
	 * @return array The function output along with relevant parser options.
	 */
	public static function followRender($parser, $frame, $params) {
		$text = isset($params[0]) ? trim(ParserPower::unescape($frame->expand($params[0]))) : '';

		$output = $text;
		$title = Title::newFromText($text);
		if ($title !== null && $title->getNamespace() !== NS_MEDIA && $title->getNamespace() > -1) {
			$page = MediaWikiServices::getInstance()->getWikiPageFactory()->newFromTitle($title);
			$target = $page->getRedirectTarget();
			if ($target !== null) {
				$output = $target->getPrefixedText();
			}
		}

		return [$output, 'noparse' => false];
	}
	
	public static function arraymapRender($parser, $frame, $args) {
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

	public static function arraymaptemplateRender($parser, $frame, $args) {
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
			$results_array[] = $parser->replaceVariables(
				implode( '', $bracketed_value ), $frame );
		}
		return implode( $new_delimiter, $results_array );
	}
	
}
