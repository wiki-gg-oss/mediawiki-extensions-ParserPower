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
 * Parser function for mapping list values (#listmap).
 */
class ListMapFunction implements ParserFunction {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'listmap';
	}

	/**
	 * This function performs the value changing operation for the listmap function.
	 *
	 * @param WikitextOperation $operation Operation to apply.
	 * @param bool $keepEmpty True to keep empty values once the operation applied, false to remove empty values.
	 * @param array $inValues Array with the input values.
	 * @param string $fieldSep Separator between fields, if any.
	 * @return array The function output.
	 */
	protected function mapList(
		WikitextOperation $operation,
		bool $keepEmpty,
		array $inValues,
		string $fieldSep = ''
	): array {
		$fieldLimit = $operation->getFieldLimit();

		$outValues = [];
		foreach ( $inValues as $i => $inValue ) {
			$outValue = $operation->apply( ListUtils::explodeValue( $fieldSep, $inValue, $fieldLimit ), $i + 1 );
			if ( $outValue !== '' || $keepEmpty ) {
				$outValues[] = $outValue;
			}
		}

		return $outValues;
	}

	/**
	 * @inheritDoc
	 */
	public function render( Parser $parser, PPFrame $frame, array $params ): string {
		$params = ParameterParser::arrange( $frame, $params );
		$params = new ParameterParser( $frame, $params, ListUtils::PARAM_OPTIONS );

		$inList = $params->get( 'list' );
		$default = $params->get( 'default' );

		if ( $inList === '' ) {
			return ParserPower::evaluateUnescaped( $parser, $frame, $default );
		}

		$template = $params->get( 'template' );
		$inSep = $params->get( 'insep' );
		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );
		$fieldSep = $params->get( 'fieldsep' );
		$indexToken = $params->get( 'indextoken' );
		$token = $params->get( 'token' );
		$tokenSep = $params->get( 'tokensep' );
		$pattern = $params->get( 'pattern' );
		$sortMode = ListUtils::decodeSortMode( $params->get( 'sortmode' ) );
		$sortOptions = ListUtils::decodeSortOptions( $params->get( 'sortoptions' ) );
		$duplicates = ListUtils::decodeDuplicates( $params->get( 'duplicates' ) );
		$countToken = $params->get( 'counttoken' );
		$intro = $params->get( 'intro' );
		$outro = $params->get( 'outro' );

		$sorter = new ListSorter( $sortOptions );

		$inValues = ListUtils::explode( $inSep, $inList );

		if ( $duplicates & ListUtils::DUPLICATES_PRESTRIP ) {
			$inValues = array_unique( $inValues );
		}

		if ( $template !== '' ) {
			if ( $sortMode & ListUtils::SORTMODE_PRE ) {
				$inValues = $sorter->sort( $inValues );
			}

			$operation = new TemplateOperation( $parser, $frame, $template );
			$outValues = $this->mapList( $operation, true, $inValues, $fieldSep );

			if ( $sortMode & ( ListUtils::SORTMODE_POST | ListUtils::SORTMODE_COMPAT ) ) {
				$outValues = $sorter->sort( $outValues );
			}
		} else {
			if (
				( $indexToken !== '' && $sortMode & ListUtils::SORTMODE_COMPAT ) ||
				$sortMode & ListUtils::SORTMODE_PRE
			) {
				$inValues = $sorter->sort( $inValues );
			}

			if ( $fieldSep !== '' ) {
				$tokens = ListUtils::explodeToken( $tokenSep, $token );
			} else {
				$tokens = [ $token ];
			}

			$operation = new PatternOperation( $parser, $frame, $pattern, $tokens, $indexToken );
			$outValues = $this->mapList( $operation, false, $inValues, $fieldSep );

			if (
				( $indexToken === '' && $sortMode & ListUtils::SORTMODE_COMPAT ) ||
				$sortMode & ListUtils::SORTMODE_POST
			) {
				$outValues = $sorter->sort( $outValues );
			}
		}

		if ( $duplicates & ListUtils::DUPLICATES_POSTSTRIP ) {
			$outValues = array_unique( $outValues );
		}

		$count = count( $outValues );
		if ( $count === 0 ) {
			return ParserPower::evaluateUnescaped( $parser, $frame, $default );
		}

		$outSep = $count > 2 ? $params->get( 'outsep' ) : '';
		$outConj = $count > 1 ? $params->get( $params->isDefined( 'outconj' ) ? 'outconj' : 'outsep' ) : '';
		if ( $outConj !== $outSep ) {
			$outConj = ' ' . trim( $outConj ) . ' ';
		}

		$outList = ListUtils::implode( $outValues, $outSep, $outConj );
		$outList = ListUtils::applyIntroAndOutro( $intro, $outList, $outro, $countToken, $count );
		return ParserPower::evaluateUnescaped( $parser, $frame, $outList );
	}
}
