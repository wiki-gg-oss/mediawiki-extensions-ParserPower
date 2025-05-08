<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function\List;

use MediaWiki\Extension\ParserPower\ListFunctions;
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
			$result = $operation->apply( ListFunctions::explodeValue( $fieldSep, $inValue, $fieldLimit ), $i + 1 );
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
		$params = new ParameterParser( $frame, $params, ListFunctions::PARAM_OPTIONS );

		$inList = $params->get( 'list' );
		$default = $params->get( 'default' );

		$keepValues = $params->get( 'keep' );
		$keepSep = $params->get( 'keepsep' );
		$keepCS = ListFunctions::decodeBool( $params->get( 'keepcs' ) );
		$removeValues = $params->get( 'remove' );
		$removeSep = $params->get( 'removesep' );
		$removeCS = ListFunctions::decodeBool( $params->get( 'removecs' ) );
		$template = $params->get( 'template' );
		$inSep = $params->get( 'insep' );
		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );
		$fieldSep = $params->get( 'fieldsep' );
		$indexToken = $params->get( 'indextoken' );
		$token = $params->get( 'token' );
		$tokenSep = $params->get( 'tokensep' );
		$tokenSep = $parser->getStripState()->unstripNoWiki( $tokenSep );
		$pattern = $params->get( 'pattern' );
		$outSep = $params->get( 'outsep' );
		$countToken = $params->get( 'counttoken' );
		$intro = $params->get( 'intro' );
		$outro = $params->get( 'outro' );

		if ( $inList === '' ) {
			return ParserPower::evaluateUnescaped( $parser, $frame, $default );
		}

		$inValues = ListFunctions::explodeList( $inSep, $inList );

		if ( $keepValues !== '' ) {
			if ( $keepSep !== '' ) {
				$keepValues = ListFunctions::explodeList( $keepSep, $keepValues );
			} else {
				$keepValues = [ ParserPower::unescape( $keepValues ) ];
			}

			$operation = new ListInclusionOperation( $keepValues, '', 'remove', $keepCS );
		} elseif ( $removeValues !== '' ) {
			if ( $removeSep !== '' ) {
				$removeValues = ListFunctions::explodeList( $removeSep, $removeValues );
			} else {
				$removeValues = [ ParserPower::unescape( $removeValues ) ];
			}

			$operation = new ListInclusionOperation( $removeValues, 'remove', '', $removeCS );
		} elseif ( $template !== '' ) {
			$operation = new TemplateOperation( $parser, $frame, $template );
		} else {
			if ( $fieldSep !== '' ) {
				$tokens = ListFunctions::explodeToken( $tokenSep, $token );
			} else {
				$tokens = [ $token ];
			}

			$operation = new PatternOperation( $parser, $frame, $pattern, $tokens, $indexToken );
		}

		$outValues = $this->filterList( $operation, $inValues, $fieldSep );

		if ( count( $outValues ) === 0 ) {
			return ParserPower::evaluateUnescaped( $parser, $frame, $default );
		}

		$count = count( $outValues );
		$outList = ListFunctions::implodeList( $outValues, $outSep );
		$outList = ListFunctions::applyIntroAndOutro( $intro, $outList, $outro, $countToken, $count );

		return ParserPower::evaluateUnescaped( $parser, $frame, $outList );
	}
}
