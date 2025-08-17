<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function\List;

use MediaWiki\Extension\ParserPower\ParameterParser;
use MediaWiki\Extension\ParserPower\ParserPowerConfig;

/**
 * Parser function for removing non-unique list values from an identity pattern (#lstuniq).
 */
final class LstUniqFunction extends ListUniqueFunction {

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
		return 'lstuniq';
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
		return [
			...parent::getParamSpec(),
			0 => 'list',
			1 => 'insep',
			2 => 'outsep',
			3 => [
				'alias' => 'uniquecs',
				'formatter' => $this->getCSFormatter()
			]
		];
	}
}
