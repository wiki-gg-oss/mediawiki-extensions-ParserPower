<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function\List;

use MediaWiki\Extension\ParserPower\Function\ParserFunctionBase;

/**
 * Parser function manipulating a list.
 */
abstract class ListFunction extends ParserFunctionBase {

	/**
	 * @inheritDoc
	 */
	public function getParamSpec(): array {
		return [
			'counttoken' => [ 'unescape' => true ],
			'csoption' => [],
			'default' => [ 'unescape' => true ],
			'duplicates' => [],
			'fieldsep' => [ 'unescape' => true ],
			'keep' => [],
			'keepcs' => [],
			'keepsep' => [ 'default' => ',' ],
			'index' => [ 'unescape' => true ],
			'indexoptions' => [],
			'indextoken' => [ 'unescape' => true ],
			'insep' => [ 'unescape' => true, 'default' => ',' ],
			'intro' => [ 'unescape' => true ],
			'length' => [ 'unescape' => true ],
			'list' => [],
			'outro' => [ 'unescape' => true ],
			'outsep' => [ 'unescape' => true, 'default' => ', ' ],
			'outconj' => [ 'unescape' => true ],
			'pattern' => [],
			'remove' => [],
			'removecs' => [],
			'removesep' => [ 'default' => ',' ],
			'sortmode' => [],
			'sortoptions' => [],
			'subsort' => [],
			'subsortoptions' => [],
			'template' => [],
			'token' => [ 'unescape' => true ],
			'tokensep' => [ 'unescape' => true, 'default' => ',' ],
			'uniquecs' => [],
			'value' => [ 'unescape' => true ]
		];
	}
}
