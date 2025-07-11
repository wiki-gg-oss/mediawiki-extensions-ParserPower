<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function\List;

/**
 * Parser function for removing non-unique list values from an identity pattern (#lstuniq).
 */
final class LstUniqFunction extends ListUniqueFunction {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'lstuniq';
	}

	/**
	 * @inheritDoc
	 */
	public function allowsNamedParams(): bool {
		return false;
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
