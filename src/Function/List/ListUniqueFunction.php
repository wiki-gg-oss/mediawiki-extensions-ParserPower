<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function\List;

use MediaWiki\Extension\ParserPower\ListUtils;
use MediaWiki\Extension\ParserPower\Operation\PatternOperation;
use MediaWiki\Extension\ParserPower\Operation\TemplateOperation;
use MediaWiki\Extension\ParserPower\Operation\WikitextOperation;
use MediaWiki\Extension\ParserPower\Parameters;
use MediaWiki\Extension\ParserPower\ParserPower;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

/**
 * Parser function for removing non-unique list values (#listunique).
 */
class ListUniqueFunction extends ListFunction {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'listunique';
	}

	/**
	 * @inheritDoc
	 */
	public function allowsNamedParams(): bool {
		return true;
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
			$key = $operation->apply( ListUtils::explodeValue( $fieldSep, $value, $fieldLimit ), $i + 1 );
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
	public function execute( Parser $parser, PPFrame $frame, Parameters $params ): string {
		$inList = $params->get( 'list' );
		$inSep = $inList !== '' ? $params->get( 'insep' ) : '';
		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );
		$inValues = ListUtils::explode( $inSep, $inList );

		if ( count( $inValues ) === 0 ) {
			return ParserPower::evaluateUnescaped( $parser, $frame, $params->get( 'default' ) );
		}

		$template = $params->get( 'template' );

		if ( $template !== '' ) {
			$fieldSep = $params->get( 'fieldsep' );
			$operation = new TemplateOperation( $parser, $frame, $template );
			$outValues = $this->reduceToUniqueValuesByKey( $operation, $inValues, $fieldSep );
		} else {
			$indexToken = $params->get( 'indextoken' );
			$token = $params->get( 'token' );
			$pattern = $params->get( 'pattern' );

			if ( ( $indexToken !== '' || $token !== '' ) && $pattern !== '' ) {
				$fieldSep = $params->get( 'fieldsep' );
				$tokenSep = $fieldSep !== '' ? $params->get( 'tokensep' ) : '';
				$tokens = ListUtils::explodeToken( $tokenSep, $token );
				$operation = new PatternOperation( $parser, $frame, $pattern, $tokens, $indexToken );
				$outValues = $this->reduceToUniqueValuesByKey( $operation, $inValues, $fieldSep );
			} else {
				$uniqueCS = ListUtils::decodeBool( $params->get( 'uniquecs' ) );
				$outValues = $this->reduceToUniqueValues( $inValues, $uniqueCS );
			}
		}

		$count = count( $outValues );
		$outSep = $count > 1 ? $params->get( 'outsep' ) : '';
		$outList = ListUtils::implode( $outValues, $outSep );

		$countToken = $params->get( 'counttoken' );
		$intro = $params->get( 'intro' );
		$outro = $params->get( 'outro' );
		$outList = ListUtils::applyIntroAndOutro( $intro, $outList, $outro, $countToken, $count );

		return ParserPower::evaluateUnescaped( $parser, $frame, $outList );
	}
}
