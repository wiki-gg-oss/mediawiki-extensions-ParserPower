<?php
/**
 * Sort Key Value Comparer Class
 *
 * @package   ParserPower
 * @author    Eyes <eyes@aeongarden.com>, Samuel Hilson <shilson@fandom.com>
 * @copyright Copyright � 2013 Eyes
 * @copyright 2019 Wikia Inc.
 * @license   GPL-2.0-or-later
 */

namespace MediaWiki\Extension\ParserPower;

class SortKeyValueComparer {
	/**
	 * The function to use to compare sort keys.
	 *
	 * @var callable
	 */
	private $mSortKeyCompare = [ ComparisonUtils::class, 'numericstrcmp' ];

	/**
	 * The function to use to compare values, if any.
	 *
	 * @var callable
	 */
	private $mValueCompare = null;

	/**
	 * Constructs a ParserPowerSortKeyComparer from the given options.
	 *
	 * @param int $sortKeyOptions The options for the key sort.
	 * @param bool $valueSort true to perform a value sort for values with the same key.
	 * @param int $valueOptions The options for the value sort.
	 */
	public function __construct( $sortKeyOptions, $valueSort, $valueOptions = 0 ) {
		$this->mSortKeyCompare = $this->getComparer( $sortKeyOptions );
		if ( $valueSort ) {
			$this->mValueCompare = $this->getComparer( $valueOptions );
		}
	}

	/**
	 * Compares a sort key-value pair where each pair is in an array with the sort key in element 0 and the value in
	 * element 1.
	 *
	 * @param array $pair1 A sort-key value pair to compare to $pair2
	 * @param array $pair2 A sort-key value pair to compare to $pair1
	 * @return int Number > 0 if str1 is less than str2; Number < 0 if str1 is greater than str2; 0 if they are equal.
	 */
	public function compare( array $pair1, array $pair2 ) {
		$result = call_user_func( $this->mSortKeyCompare, $pair1[0], $pair2[0] );

		if ( $result === 0 ) {
			if ( $this->mValueCompare ) {
				return call_user_func( $this->mValueCompare, $pair1[1], $pair2[1] );
			} else {
				return 0;
			}
		} else {
			return $result;
		}
	}

	/**
	 * Get Comparer class
	 *
	 * @param int $options
	 * @return void
	 */
	private function getComparer( $options ) {
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
