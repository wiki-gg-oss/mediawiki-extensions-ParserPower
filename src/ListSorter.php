<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower;

class ListSorter {

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
		$this->valueCompare = $this->getComparer( $sortOptions );
		if ( $subSortOptions !== null ) {
			$this->subValueCompare = $this->getComparer( $subSortOptions );
		}
	}

	/**
	 * Sorts a list of pairs of values.
	 *
	 * @param array $pairs Value pairs to sort.
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
	 * Get Comparer class
	 *
	 * @param int $options
	 * @return callable
	 */
	private function getComparer( int $options ): callable {
		if ( $options & ListFunctions::SORT_NUMERIC ) {
			if ( $options & ListFunctions::SORT_DESC ) {
				return [ ComparisonUtils::class, 'numericrstrcmp' ];
			} else {
				return [ ComparisonUtils::class, 'numericstrcmp' ];
			}
		} else {
			if ( $options & ListFunctions::SORT_CS ) {
				if ( $options & ListFunctions::SORT_DESC ) {
					return [ ComparisonUtils::class, 'rstrcmp' ];
				} else {
					return 'strcmp';
				}
			} else {
				if ( $options & ListFunctions::SORT_DESC ) {
					return [ ComparisonUtils::class, 'rstrcasecmp' ];
				} else {
					return 'strcasecmp';
				}
			}
		}
	}
}
