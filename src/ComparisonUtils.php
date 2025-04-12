<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower;

final class ComparisonUtils {
	/**
	 * The function compares two strings by numerical value, attempting to match the observed behavior of the built-in
	 * sort function using ListSorter::NUMERIC.
	 *
	 * @param string $string1 A string to compare to $string2.
	 * @param string $string2 A string to compare to $string1.
	 * @return int Number > 0 if str1 is less than str2; Number < 0 if str1 is greater than str2; 0 if they are equal.
	 */
	public static function numericstrcmp( $string1, $string2 ) {
		return ( is_numeric( $string1 ) ? $string1 : 0 ) - ( is_numeric( $string2 ) ? $string2 : 0 );
	}

	/**
	 * The function compares two strings by numerical value, attempting to match the observed behavior of the built-in
	 * sort function using ListSorter::NUMERIC, except that it gives negated results.
	 *
	 * @param string $string1 A string to compare to $string2.
	 * @param string $string2 A string to compare to $string1.
	 * @return int Number > 0 if str1 is less than str2; Number < 0 if str1 is greater than str2; 0 if they are equal.
	 */
	public static function numericrstrcmp( $string1, $string2 ) {
		return ( is_numeric( $string2 ) ? $string2 : 0 ) - ( is_numeric( $string1 ) ? $string1 : 0 );
	}

	/**
	 * The function returns the negated return value of strcmp for the given strings.
	 *
	 * @param string $string1 A string to compare to $string2.
	 * @param string $string2 A string to compare to $string1.
	 * @return int Number > 0 if str1 is less than str2; Number < 0 if str1 is greater than str2; 0 if they are equal.
	 */
	public static function rstrcmp( $string1, $string2 ) {
		return strcmp( $string2, $string1 );
	}

	/**
	 * The function returns the negated return value of strcasecmp for the given strings.
	 *
	 * @param string $string1 A string to compare to $string2.
	 * @param string $string2 A string to compare to $string1.
	 * @return int Number > 0 if str1 is less than str2; Number < 0 if str1 is greater than str2; 0 if they are equal.
	 */
	public static function rstrcasecmp( $string1, $string2 ) {
		return strcasecmp( $string2, $string1 );
	}
}
