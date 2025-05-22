<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function\List;

use MediaWiki\Extension\ParserPower\ListSorter;
use MediaWiki\Extension\ParserPower\ListUtils;
use MediaWiki\Extension\ParserPower\Operation\PatternOperation;
use MediaWiki\Extension\ParserPower\Operation\TemplateOperation;
use MediaWiki\Extension\ParserPower\Operation\WikitextOperation;
use MediaWiki\Extension\ParserPower\ParameterParser;
use MediaWiki\Extension\ParserPower\ParserPower;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;
use MediaWiki\Extension\ParserPower\Function\ParserFunctionBase;

/**
 * Parser function for merging list values (#listmerge).
 */
class ListMergeFunction extends ParserFunctionBase {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'listmerge';
	}

	/**
	 * @inheritDoc
	 */
	public function allowsNamedParams(): bool {
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function getParamSpec(): array {
		return ListUtils::PARAM_OPTIONS;
	}

	/**
	 * This function performs repeated merge passes until either the input array is merged to a single value, or until
	 * a merge pass is completed that does not perform any further merges (pre- and post-pass array count is the same).
	 * Each merge pass operates by performing a conditional on all possible pairings of items, immediately merging two
	 * if the conditional indicates it should and reducing the possible pairings. The logic for the conditional and
	 * the actual merge process is supplied through a user-defined function.
	 *
	 * @param WikitextOperation $matchOperation Operation to apply for the matching process.
	 * @param WikitextOperation $mergeOperation Operation to apply for the merging process.
	 * @param array $values Array with the input values.
	 * @param string $fieldSep Separator between fields, if any.
	 * @param ?int $fieldOffset Number of fields that the first value should cover.
	 * @return array An array with the output values.
	 */
	private function iterativeListMerge(
		WikitextOperation $matchOperation,
		WikitextOperation $mergeOperation,
		array $values,
		string $fieldSep = '',
		?int $fieldOffset = null
	): array {
		$checkedPairs = [];

		do {
			$preCount = $count = count( $values );

			for ( $i1 = 0; $i1 < $count; ++$i1 ) {
				$value1 = $values[$i1];
				$shift = 0;

				for ( $i2 = $i1 + 1; $i2 < $count; ++$i2 ) {
					$value2 = $values[$i2];
					unset( $values[$i2] );

					$fields1 = ListUtils::explodeValue( $fieldSep, $value1, $fieldOffset );
					$offset = $fieldOffset ?? count( $fields1 );

					if ( isset( $checkedPairs[$value1][$value2] ) ) {
						$doMerge = $checkedPairs[$value1][$value2];
					} else {
						$fieldLimit = $matchOperation->getFieldLimit();
						if ( $fieldLimit !== null ) {
							$fieldLimit = $fieldLimit - $offset;
						}

						$fields = $fields1;
						foreach ( ListUtils::explodeValue( $fieldSep, $value2, $fieldLimit ) as $i => $field ) {
							$fields[$offset + $i] = $field;
						}

						$doMerge = $matchOperation->apply( $fields );
						$doMerge = ListUtils::decodeBool( $doMerge );
						$checkedPairs[$value1][$value2] = $doMerge;
					}

					if ( $doMerge ) {
						$fieldLimit = $mergeOperation->getFieldLimit();
						if ( $fieldLimit !== null ) {
							$fieldLimit = $fieldLimit - $offset;
						}

						$fields = $fields1;
						foreach ( ListUtils::explodeValue( $fieldSep, $value2, $fieldLimit ) as $i => $field ) {
							$fields[$offset + $i] = $field;
						}

						$value1 = $mergeOperation->apply( $fields );
						$shift += 1;
					} else {
						$values[$i2 - $shift] = $value2;
					}
				}

				$values[$i1] = $value1;
				$count -= $shift;
			}
		} while ( $count < $preCount && $count > 1 );

		return $values;
	}

	/**
	 * @inheritDoc
	 */
	public function execute( Parser $parser, PPFrame $frame, ParameterParser $params ): string {
		$inList = $params->get( 'list' );
		$inSep = $inList !== '' ? $params->get( 'insep' ) : '';
		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );
		$inValues = ListUtils::explode( $inSep, $inList );

		if ( count( $inValues ) === 0 ) {
			return ParserPower::evaluateUnescaped( $parser, $frame, $params->get( 'default' ) );
		}

		$matchTemplate = $params->get( 'matchtemplate' );
		$mergeTemplate = $params->get( 'mergetemplate' );
		$fieldSep = $params->get( 'fieldsep' );

		$sortMode = ListUtils::decodeSortMode( $params->get( 'sortmode' ) );
		$sortOptions = $sortMode > 0 ? ListUtils::decodeSortOptions( $params->get( 'sortoptions' ) ) : 0;
		$sorter = new ListSorter( $sortOptions );

		if ( $sortMode & ListUtils::SORTMODE_PRE ) {
			$inValues = $sorter->sort( $inValues );
		}

		if ( $matchTemplate === '' || $mergeTemplate === '' ) {
			$tokenSep = $fieldSep !== '' ? $params->get( 'tokensep' ) : '';
			$tokens1 = ListUtils::explodeToken( $tokenSep, $params->get( 'token1' ) );
			$tokens2 = ListUtils::explodeToken( $tokenSep, $params->get( 'token2' ) );
			$tokens = [ ...$tokens1, ...$tokens2 ];
			$fieldOffset = count( $tokens1 );
		}

		if ( $matchTemplate !== '' ) {
			$matchOperation = new TemplateOperation( $parser, $frame, $matchTemplate );
		} else {
			$matchPattern = $params->get( 'matchpattern' );
			$matchOperation = new PatternOperation( $parser, $frame, $matchPattern, $tokens );
		}

		if ( $mergeTemplate !== '' ) {
			$mergeOperation = new TemplateOperation( $parser, $frame, $mergeTemplate );
		} else {
			$mergePattern = $params->get( 'mergepattern' );
			$mergeOperation = new PatternOperation( $parser, $frame, $mergePattern, $tokens );
		}

		$outValues = $this->iterativeListMerge( $matchOperation, $mergeOperation, $inValues, $fieldSep, $fieldOffset ?? null );

		if ( $sortMode & ( ListUtils::SORTMODE_POST | ListUtils::SORTMODE_COMPAT ) ) {
			$outValues = $sorter->sort( $outValues );
		}

		$count = count( $outValues );
		if ( $count === 0 ) {
			return ParserPower::evaluateUnescaped( $parser, $frame, $params->get( 'default' ) );
		}

		$outSep = $count > 1 ? $params->get( 'outsep' ) : '';
		$outList = ListUtils::implode( $outValues, $outSep );

		$countToken = $params->get( 'counttoken' );
		$intro = $params->get( 'intro' );
		$outro = $params->get( 'outro' );
		$outList = ListUtils::applyIntroAndOutro( $intro, $outList, $outro, $countToken, $count );

		return ParserPower::evaluateUnescaped( $parser, $frame, $outList );
	}
}
