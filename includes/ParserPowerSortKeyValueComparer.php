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

namespace ParserPower;

class ParserPowerSortKeyValueComparer {
	/**
	 * The function to use to compare sort keys.
	 *
	 * @var callable
	 */
	private $mSortKeyCompare = [ ParserPowerCompare::class, 'numericstrcmp' ];

	/**
	 * The function to use to compare values, if any.
	 *
	 * @var callable
	 */
	private $mValueCompare = null;

	/**
	 * Constructs a ParserPowerSortKeyComparer from the given options.
	 *
	 * @param int  $sortKeyOptions The options for the key sort.
	 * @param bool $valueSort      true to perform a value sort for values with the same key.
	 * @param int  $valueOptions   The options for the value sort.
	 */
	public function __construct($sortKeyOptions, $valueSort, $valueOptions = 0) {
		$this->mSortKeyCompare = $this->getComparer($sortKeyOptions);
		if ($valueSort) {
			$this->mValueCompare = $this->getComparer($valueOptions);
		}
	}

	/**
	 * Compares a sort key-value pair where each pair is in an array with the sort key in element 0 and the value in
	 * element 1.
	 *
	 * @param Array $pair1 A sort-key value pair to compare to $pair2
	 * @param Array $pair2 A sort-key value pair to compare to $pair1
	 *
	 * @return int Number > 0 if str1 is less than str2; Number < 0 if str1 is greater than str2; 0 if they are equal.
	 */
	public function compare($pair1, $pair2) {
		$result = call_user_func($this->mSortKeyCompare, $pair1[0], $pair2[0]);

		if ($result === 0) {
			if ($this->mValueCompare) {
				return call_user_func($this->mValueCompare, $pair1[1], $pair2[1]);
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
	 *
	 * @return void
	 */
	private function getComparer($options) {
		if ($options & ParserPowerLists::SORT_NUMERIC) {
			if ($options & ParserPowerLists::SORT_DESC) {
				return [ ParserPowerCompare::class, 'numericrstrcmp' ];
			} else {
				return [ ParserPowerCompare::class, 'numericstrcmp' ];
			}
		} else {
			if ($options & ParserPowerLists::SORT_CS) {
				if ($options & ParserPowerLists::SORT_DESC) {
					return [ ParserPowerCompare::class, 'rstrcmp' ];
				} else {
					return 'strcmp';
				}
			} else {
				if ($options & ParserPowerLists::SORT_DESC) {
					return [ ParserPowerCompare::class, 'rstrcasecmp' ];
				} else {
					return 'strcasecmp';
				}
			}
		}
	}
}
