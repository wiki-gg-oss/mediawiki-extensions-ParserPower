<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function\List;

use MediaWiki\Extension\ParserPower\ListUtils;
use MediaWiki\Extension\ParserPower\Operation\ListInclusionOperation;
use MediaWiki\Extension\ParserPower\Operation\PatternOperation;
use MediaWiki\Extension\ParserPower\Operation\TemplateOperation;
use MediaWiki\Extension\ParserPower\Operation\WikitextOperation;
use MediaWiki\Extension\ParserPower\ParameterParser;
use MediaWiki\Extension\ParserPower\ParserPower;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;
use MediaWiki\Extension\ParserPower\Function\ParserFunction;

/**
 * Parser function for filtering list values (#listfilter).
 */
class ListFilterFunction implements ParserFunction {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'listfilter';
	}

	/**
	 * This function performs the filtering operation for the listfilter function.
	 *
	 * @param WikitextOperation $operation Operation to apply.
	 * @param array $inValues Array with the input values.
	 * @param string $fieldSep Separator between fields, if any.
	 * @return array The array stripped of any values with non-unique keys.
	 */
	protected function filterList( WikitextOperation $operation, array $inValues, string $fieldSep = '' ): array {
		$fieldLimit = $operation->getFieldLimit();

		$outValues = [];
		foreach ( $inValues as $i => $inValue ) {
			$result = $operation->apply( ListUtils::explodeValue( $fieldSep, $inValue, $fieldLimit ), $i + 1 );
			if ( strtolower( $result ) !== 'remove' ) {
				$outValues[] = $inValue;
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

		if ( $inList === '' ) {
			return ParserPower::evaluateUnescaped( $parser, $frame, $params->get( 'default' ) );
		}

		$inSep = $params->get( 'insep' );
		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );
		$inValues = ListUtils::explode( $inSep, $inList );

		if ( count( $inValues ) === 0 ) {
			return ParserPower::evaluateUnescaped( $parser, $frame, $params->get( 'default' ) );
		}

		$keepValues = $params->get( 'keep' );

		if ( $keepValues !== '' ) {
			$keepSep = $params->get( 'keepsep' );
			if ( $keepSep !== '' ) {
				$keepValues = ListUtils::explode( $keepSep, $keepValues );
			} else {
				$keepValues = [ ParserPower::unescape( $keepValues ) ];
			}

			$keepCS = ListUtils::decodeBool( $params->get( 'keepcs' ) );
			$operation = new ListInclusionOperation( $keepValues, '', 'remove', $keepCS );
		} else {
			$removeValues = $params->get( 'remove' );

			if ( $removeValues !== '' ) {
				$removeSep = $params->get( 'removesep' );
				if ( $removeSep !== '' ) {
					$removeValues = ListUtils::explode( $removeSep, $removeValues );
				} else {
					$removeValues = [ ParserPower::unescape( $removeValues ) ];
				}

				$removeCS = ListUtils::decodeBool( $params->get( 'removecs' ) );
				$operation = new ListInclusionOperation( $removeValues, 'remove', '', $removeCS );
			} else {
				$template = $params->get( 'template' );
				$fieldSep = $params->get( 'fieldsep' );

				if ( $template !== '' ) {
					$operation = new TemplateOperation( $parser, $frame, $template );
				} else {
					$indexToken = $params->get( 'indextoken' );
					$tokenSep = $fieldSep !== '' ? $params->get( 'tokensep' ) : '';
					$tokenSep = $parser->getStripState()->unstripNoWiki( $tokenSep );
					$tokens = ListUtils::explodeToken( $tokenSep, $params->get( 'token' ) );
					$pattern = $params->get( 'pattern' );
					$operation = new PatternOperation( $parser, $frame, $pattern, $tokens, $indexToken );
				}
			}
		}

		$outValues = $this->filterList( $operation, $inValues, $fieldSep ?? '' );

		if ( count( $outValues ) === 0 ) {
			return ParserPower::evaluateUnescaped( $parser, $frame, $params->get( 'default' ) );
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
