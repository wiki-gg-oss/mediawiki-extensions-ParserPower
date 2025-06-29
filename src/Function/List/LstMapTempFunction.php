<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function\List;

/**
 * Parser function for mapping list values from a template (#lstmaptemp).
 */
final class LstMapTempFunction extends ListMapFunction {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'lstmaptemp';
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
			1 => 'template',
			2 => 'insep',
			3 => 'outsep',
			4 => 'sortmode',
			5 => 'sortoptions'
		];
	}
}
