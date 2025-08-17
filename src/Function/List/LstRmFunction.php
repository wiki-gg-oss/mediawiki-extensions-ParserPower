<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function\List;

use MediaWiki\Extension\ParserPower\ParameterParser;
use MediaWiki\Extension\ParserPower\ParserPowerConfig;

/**
 * Parser function for filtering list values from an exclusion value (#lstrm).
 */
final class LstRmFunction extends ListFilterFunction {

	/**
	 * @var bool Whether named parameters are allowed, and should be split from numbered arguments.
	 */
	private string $legacyNamedExpansion;

	/**
	 * @param ParserPowerConfig $config
	 */
	public function __construct( ParserPowerConfig $config ) {
		$this->legacyNamedExpansion = $config->get( 'LstFunctionNamedExpansionCompat' );
		parent::__construct();
	}

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'lstrm';
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
			0 => 'remove',
			1 => 'list',
			2 => 'insep',
			3 => 'outsep',
			4 => [
				'alias' => 'removecs',
				'formatter' => $this->getCSFormatter()
			]
		];

		$paramSpec['removesep']['default'] = '';

		return $paramSpec;
	}
}
