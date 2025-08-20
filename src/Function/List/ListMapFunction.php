<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function\List;

use MediaWiki\Extension\ParserPower\ListSorter;
use MediaWiki\Extension\ParserPower\ListUtils;
use MediaWiki\Extension\ParserPower\Operation\PatternOperation;
use MediaWiki\Extension\ParserPower\Operation\TemplateOperation;
use MediaWiki\Extension\ParserPower\Operation\WikitextOperation;
use MediaWiki\Extension\ParserPower\ParameterParser;
use MediaWiki\Extension\ParserPower\Parameters;
use MediaWiki\Extension\ParserPower\ParserPower;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

/**
 * Parser function for mapping list values (#listmap).
 */
class ListMapFunction extends ListFunction {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'listmap';
	}

	/**
	 * @inheritDoc
	 */
	public function getParserFlags(): int {
		return ParameterParser::ALLOWS_NAMED;
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
	public function execute( Parser $parser, PPFrame $frame, Parameters $params ): string {
		$inList = $params->get( 'list' );
		$inSep = $inList !== '' ? $params->get( 'insep' ) : '';
		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );
		$inValues = ListUtils::explode( $inSep, $inList );

		if ( empty( $inValues ) ) {
			return ParserPower::evaluateUnescaped( $parser, $frame, $params->get( 'default' ) );
		}

		$template = $params->get( 'template' );
		$fieldSep = $params->get( 'fieldsep' );

		$sortMode = $params->get( 'sortmode' );
		$sortOptions = $sortMode > 0 ? $params->get( 'sortoptions' ) : 0;
		$sorter = new ListSorter( $sortOptions );

		$duplicates = $params->get( 'duplicates' );

		if ( $duplicates & self::DUPLICATES_PRESTRIP ) {
			$inValues = array_unique( $inValues );
		}

		if ( $sortMode & self::SORTMODE_PRE ) {
			$inValues = $sorter->sort( $inValues );
		}

		if ( $template !== '' ) {
			$operation = new TemplateOperation( $parser, $frame, $template );
			$outValues = $this->mapList( $operation, true, $inValues, $fieldSep );
		} else {
			$indexToken = $params->get( 'indextoken' );
			$tokenSep = $fieldSep !== '' ? $params->get( 'tokensep' ) : '';
			$tokens = ListUtils::explodeToken( $tokenSep, $params->get( 'token' ) );
			$pattern = $params->get( 'pattern' );

			$operation = new PatternOperation( $parser, $frame, $pattern, $tokens, $indexToken );
			$outValues = $this->mapList( $operation, false, $inValues, $fieldSep );
		}

		if ( $sortMode & ( self::SORTMODE_POST | self::SORTMODE_COMPAT ) ) {
			$outValues = $sorter->sort( $outValues );
		}

		if ( $duplicates & self::DUPLICATES_POSTSTRIP ) {
			$outValues = array_unique( $outValues );
		}

		if ( empty( $outValues ) ) {
			return ParserPower::evaluateUnescaped( $parser, $frame, $params->get( 'default' ) );
		} else {
			return ParserPower::evaluateUnescaped( $parser, $frame, $this->implodeOutList( $params, $outValues ) );
		}
	}
}
