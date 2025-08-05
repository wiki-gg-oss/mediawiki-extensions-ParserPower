<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function\List;

use MediaWiki\Extension\ParserPower\ParameterParser;
use MediaWiki\Extension\ParserPower\ParserPowerConfig;

/**
 * Parser function for mapping list values from a pattern (#lstmap).
 */
final class LstMapFunction extends ListMapFunction {

	/**
	 * @var bool Whether named parameters are allowed, and should be split from numbered arguments.
	 */
	private string $legacyNamedExpansion;
	/**
	 * @var bool Whether patterns and tokens should be expanded after token replacements.
	 */
	private bool $useLegacyExpansion;

	/**
	 * @param ParserPowerConfig $config
	 */
	public function __construct( ParserPowerConfig $config ) {
		$this->legacyNamedExpansion = $config->get( 'LstFunctionNamedExpansionCompat' );
		$this->useLegacyExpansion = $config->get( 'LstmapExpansionCompat' );
		parent::__construct();
	}

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'lstmap';
	}

	/**
	 * @inheritDoc
	 */
	public function getParserFlags(): int {
		if ( $this->legacyNamedExpansion === 'old' ) {
			return 0;
		} elseif ( $this->legacyNamedExpansion === 'tracking-old' ) {
			return ParameterParser::TRACKS_NAMED_VALUES;
		} else {
			return ParameterParser::ALLOWS_NAMED;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getParamSpec(): array {
		$paramSpec = [
			...parent::getParamSpec(),
			0 => 'list',
			1 => 'insep',
			2 => 'token',
			3 => 'pattern',
			4 => 'outsep',
			5 => 'sortmode',
			6 => 'sortoptions'
		];

		$legacyExpansionFlags = $this->useLegacyExpansion ? [ 'novars' => true ] : [];
		$paramSpec['token'] = [ ...$paramSpec['token'], 'default' => 'x', ...$legacyExpansionFlags ];
		$paramSpec['pattern'] = [ ...$paramSpec['pattern'], 'default' => 'x', ...$legacyExpansionFlags ];

		return $paramSpec;
	}
}
