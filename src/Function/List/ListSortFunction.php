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
use MediaWiki\Extension\ParserPower\Function\ParserFunction;

/**
 * Parser function for sorting list values (#listsort).
 */
class ListSortFunction implements ParserFunction {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'listsort';
	}

	/**
	 * Generates the sort keys. This returns an array of the values where each element is an array with the sort key
	 * in element 0 and the value in element 1.
	 *
	 * @param WikitextOperation $operation Operation to apply.
	 * @param array $values Array with the input values.
	 * @param string $fieldSep Separator between fields, if any.
	 * @return array An array where each value has been paired with a sort key in a two-element array.
	 */
	private function generateSortKeys( WikitextOperation $operation, array $values, string $fieldSep = '' ): array {
		$fieldLimit = $operation->getFieldLimit();

		$pairedValues = [];
		foreach ( $values as $i => $value ) {
			$key = $operation->apply( ListUtils::explodeValue( $fieldSep, $value, $fieldLimit ), $i + 1 );
			$pairedValues[] = [ $key, $value ];
		}

		return $pairedValues;
	}

	/**
	 * This takes an array where each element is an array with a sort key in element 0 and a value in element 1, and it
	 * returns an array with just the values.
	 *
	 * @param array $pairedValues An array with values paired with sort keys.
	 * @return array An array with just the values.
	 */
	private function discardSortKeys( array $pairedValues ): array {
		$values = [];

		foreach ( $pairedValues as $pairedValue ) {
			$values[] = $pairedValue[1];
		}

		return $values;
	}

	/**
	 * @inheritDoc
	 */
	public function render( Parser $parser, PPFrame $frame, array $params ): string {
		$params = ParameterParser::arrange( $frame, $params );
		$params = new ParameterParser( $frame, $params, ListUtils::PARAM_OPTIONS );

		$inList = $params->get( 'list' );

		if ( $inList === '' ) {
			return ParserPower::evaluateUnescaped( $parser, $frame, $params->get( 'default' ) );
		}

		$inSep = $params->get( 'insep' );
		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );
		$values = ListUtils::explode( $inSep, $inList );

		if ( count( $values ) === 0 ) {
			return ParserPower::evaluateUnescaped( $parser, $frame, $params->get( 'default' ) );
		}

		$template = $params->get( 'template' );
		$sortOptions = $params->get( 'sortoptions' );
		$subsort = ListUtils::decodeBool( $params->get( 'subsort' ) );
		$subsortOptions = ListUtils::decodeSortOptions( $params->get( 'subsortoptions' ) );
		$duplicates = ListUtils::decodeDuplicates( $params->get( 'duplicates' ) );

		if ( !$subsort ) {
			$subsortOptions = null;
		}

		if ( $duplicates & ListUtils::DUPLICATES_STRIP ) {
			$values = array_unique( $values );
		}

		if ( $template !== '' ) {
			$fieldSep = $params->get( 'fieldsep' );
			$sortOptions = ListUtils::decodeSortOptions( $sortOptions, ListSorter::NUMERIC );
			$sorter = new ListSorter( $sortOptions, $subsortOptions );
			$operation = new TemplateOperation( $parser, $frame, $template );

			$pairedValues = $this->generateSortKeys( $operation, $values, $fieldSep );
			$sorter->sortPairs( $pairedValues );
			$values = $this->discardSortKeys( $pairedValues );
		} else {
			$indexToken = $params->get( 'indextoken' );
			$token = $params->get( 'token' );
			$pattern = $params->get( 'pattern' );

			if ( ( $indexToken !== '' || $token !== '' ) && $pattern !== '' ) {
				$fieldSep = $params->get( 'fieldsep' );
				$tokenSep = $params->get( 'tokensep' );

				if ( $fieldSep !== '' ) {
					$tokens = ListUtils::explodeToken( $tokenSep, $token );
				} else {
					$tokens = [ $token ];
				}

				$sortOptions = ListUtils::decodeSortOptions( $sortOptions, ListSorter::NUMERIC );
				$sorter = new ListSorter( $sortOptions, $subsortOptions );
				$operation = new PatternOperation( $parser, $frame, $pattern, $tokens, $indexToken );

				$pairedValues = $this->generateSortKeys( $operation, $values, $fieldSep );
				$sorter->sortPairs( $pairedValues );
				$values = $this->discardSortKeys( $pairedValues );
			} else {
				$sortOptions = ListUtils::decodeSortOptions( $sortOptions );
				$sorter = new ListSorter( $sortOptions );
				$values = $sorter->sort( $values );
			}
		}

		if ( count( $values ) === 0 ) {
			return ParserPower::evaluateUnescaped( $parser, $frame, $params->get( 'default' ) );
		}

		$count = count( $values );
		$outSep = $count > 1 ? $params->get( 'outsep' ) : '';
		$outList = ListUtils::implode( $values, $outSep );

		$countToken = $params->get( 'counttoken' );
		$intro = $params->get( 'intro' );
		$outro = $params->get( 'outro' );
		$outList = ListUtils::applyIntroAndOutro( $intro, $outList, $outro, $countToken, $count );

		return ParserPower::evaluateUnescaped( $parser, $frame, $outList );
	}
}
