<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower;

/**
 * Sorting method for lists of values, optionally paired with sub-values.
 */
final class ListSorter {

	/**
	 * Flag for numeric sorting, instead of alphanumeric.
	 */
	public const NUMERIC = 4;
	/**
	 * Flag for case sensitive sorting. 0 as this is a default mode, and ignored in numeric sorts.
	 */
	public const CASE_SENSITIVE = 2;
	/**
	 * Flag for sorting in descending order.
	 */
	public const DESCENDING = 1;

	/**
	 * Function to use to compare values.
	 *
	 * @var callable
	 */
	private $valueCompare;

	/**
	 * Function to use to compare sub-values, if any.
	 *
	 * @var ?callable
	 */
	private $subValueCompare = null;

	/**
	 * @param int $sortOptions Options for the value sort.
	 * @param ?int $subSortOptions Options for the sub-value sort, null to not sort sub-values.
	 */
	public function __construct( int $sortOptions, ?int $subSortOptions = null ) {
		$this->valueCompare = self::getComparer( $sortOptions );
		if ( $subSortOptions !== null ) {
			$this->subValueCompare = self::getComparer( $subSortOptions );
		}
	}

	/**
	 * Sorts a list of values.
	 *
	 * @param array &$values Values to sort.
	 * @return array The sorted list of values.
	 */
	public function sort( array &$values ): array {
		usort( $values, $this->valueCompare );
		return $values;
	}

	/**
	 * Sorts a list of pairs of values.
	 *
	 * @param array &$pairs Value pairs to sort.
	 * @return array The sorted list of value pairs.
	 */
	public function sortPairs( array &$pairs ): array {
		usort( $pairs, [ $this, 'comparePairs' ] );
		return $pairs;
	}

	/**
	 * Compares two pairs of values, with the first one given priority.
	 *
	 * @param array $pair1 Value pair to compare to $pair2.
	 * @param array $pair2 Value pair to compare to $pair1.
	 * @return int Number > 0 if $pair1 is less than $pair2, Number < 0 if $pair1 is greater than $pair2, or 0 if they are equal.
	 */
	private function comparePairs( array $pair1, array $pair2 ): int {
		$result = call_user_func( $this->valueCompare, $pair1[0], $pair2[0] );

		if ( $result !== 0 || $this->subValueCompare === null ) {
			return $result;
		} else {
			return call_user_func( $this->subValueCompare, $pair1[1], $pair2[1] );
		}
	}

	/**
	 * Get the comparison function associated to a given set of sort options.
	 *
	 * @param int $options Options for a value or sub-value sort.
	 * @return callable A comparison function.
	 */
	private static function getComparer( int $options ): callable {
		if ( $options & self::NUMERIC ) {
			if ( $options & self::DESCENDING ) {
				return [ ComparisonUtils::class, 'numericrstrcmp' ];
			} else {
				return [ ComparisonUtils::class, 'numericstrcmp' ];
			}
		} else {
			if ( $options & self::CASE_SENSITIVE ) {
				if ( $options & self::DESCENDING ) {
					return [ ComparisonUtils::class, 'rstrcmp' ];
				} else {
					return 'strcmp';
				}
			} else {
				if ( $options & self::DESCENDING ) {
					return [ ComparisonUtils::class, 'rstrcasecmp' ];
				} else {
					return 'strcasecmp';
				}
			}
		}
	}
}
