<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function\List;

use MediaWiki\Extension\ParserPower\ListFunctions;
use MediaWiki\Extension\ParserPower\Operation\PatternOperation;
use MediaWiki\Extension\ParserPower\Operation\TemplateOperation;
use MediaWiki\Extension\ParserPower\Operation\WikitextOperation;
use MediaWiki\Extension\ParserPower\ParameterParser;
use MediaWiki\Extension\ParserPower\ParserPower;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;
use MediaWiki\Extension\ParserPower\Function\ParserFunction;

/**
 * Parser function for removing non-unique list values (#listunique).
 */
class ListUniqueFunction implements ParserFunction {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'listunique';
	}

	/**
	 * This function reduces an array to unique values.
	 *
	 * @param array $values The array of values to reduce to unique values.
	 * @param bool $valueCS true to determine uniqueness case-sensitively, false to determine it case-insensitively
	 * @return array The function output.
	 */
	protected function reduceToUniqueValues( array $values, bool $valueCS ): array {
		if ( $valueCS ) {
			return array_unique( $values );
		} else {
			return array_intersect_key( $values, array_unique( array_map( 'strtolower', $values ) ) );
		}
	}

	/**
	 * This function performs the reduction to unique values operation for the listunique function.
	 *
	 * @param WikitextOperation $operation Operation to apply.
	 * @param array $inValues Array with the input values.
	 * @param string $fieldSep Separator between fields, if any.
	 * @return array The array stripped of any values with non-unique keys.
	 */
	private function reduceToUniqueValuesByKey(
		WikitextOperation $operation,
		array $inValues,
		string $fieldSep = ''
	): array {
		$fieldLimit = $operation->getFieldLimit();

		$previousKeys = [];
		$outValues = [];
		foreach ( $inValues as $i => $value ) {
			$key = $operation->apply( ListFunctions::explodeValue( $fieldSep, $value, $fieldLimit ), $i + 1 );
			if ( !in_array( $key, $previousKeys ) ) {
				$previousKeys[] = $key;
				$outValues[] = $value;
			}
		}

		return $outValues;
	}

	/**
	 * @inheritDoc
	 */
	public function render( Parser $parser, PPFrame $frame, array $params ): string {
		$params = ParameterParser::arrange( $frame, $params );
		$params = new ParameterParser( $frame, $params, ListFunctions::PARAM_OPTIONS );

		$inList = $params->get( 'list' );
		$default = $params->get( 'default' );

		$uniqueCS = ListFunctions::decodeBool( $params->get( 'uniquecs' ) );
		$template = $params->get( 'template' );
		$inSep = $params->get( 'insep' );
		$fieldSep = $params->get( 'fieldsep' );
		$indexToken = $params->get( 'indextoken' );
		$token = $params->get( 'token' );
		$tokenSep = $params->get( 'tokensep' );
		$pattern = $params->get( 'pattern' );
		$outSep = $params->get( 'outsep' );
		$countToken = $params->get( 'counttoken' );
		$intro = $params->get( 'intro' );
		$outro = $params->get( 'outro' );

		if ( $inList === '' ) {
			return ParserPower::evaluateUnescaped( $parser, $frame, $default );
		}

		$inValues = ListFunctions::explodeList( $inSep, $inList );

		if ( $fieldSep !== '' ) {
			$tokens = ListFunctions::explodeToken( $tokenSep, $token );
		} else {
			$tokens = [ $token ];
		}

		if ( $template !== '' ) {
			$operation = new TemplateOperation( $parser, $frame, $template );
			$outValues = $this->reduceToUniqueValuesByKey( $operation, $inValues, $fieldSep );
		} elseif ( ( $indexToken !== '' || $token !== '' ) && $pattern !== '' ) {
			$operation = new PatternOperation( $parser, $frame, $pattern, $tokens, $indexToken );
			$outValues = $this->reduceToUniqueValuesByKey( $operation, $inValues, $fieldSep );
		} else {
			$outValues = $this->reduceToUniqueValues( $inValues, $uniqueCS );
		}

		$count = count( $outValues );
		$outList = ListFunctions::implodeList( $outValues, $outSep );
		$outList = ListFunctions::applyIntroAndOutro( $intro, $outList, $outro, $countToken, $count );
		return ParserPower::evaluateUnescaped( $parser, $frame, $outList );
	}
}
