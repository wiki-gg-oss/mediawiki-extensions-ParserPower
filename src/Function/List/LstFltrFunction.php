<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function\List;

use MediaWiki\Extension\ParserPower\ListUtils;
use MediaWiki\Extension\ParserPower\Operation\ListInclusionOperation;
use MediaWiki\Extension\ParserPower\Parameters;
use MediaWiki\Extension\ParserPower\ParserPower;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

/**
 * Parser function for filtering list values from an inclusion list (#lstfltr).
 */
final class LstFltrFunction extends ListFilterFunction {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'lstfltr';
	}

	/**
	 * @inheritDoc
	 */
	public function getParserFlags(): int {
		return 0;
	}

	/**
	 * @inheritDoc
	 */
	public function getParamSpec(): array {
		$paramSpec = [
			...parent::getParamSpec(),
			0 => 'keep',
			1 => 'keepsep',
			2 => 'list',
			3 => 'insep',
			4 => 'outsep',
			5 => [
				'alias' => 'keepcs',
				'formatter' => $this->getCSFormatter()
			]
		];

		$paramSpec['pattern']['default'] = 'remove';

		return $paramSpec;
	}
}
