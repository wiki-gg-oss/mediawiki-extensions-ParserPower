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
			'insep1' => [ 'unescape' => true, 'default' => ',' ],
			'insep2' => [ 'unescape' => true, 'default' => ',' ],
			'intro' => [ 'unescape' => true ],
			'length' => [ 'unescape' => true ],
			'list' => [],
			'list1' => [],
			'list2' => [],
			'outro' => [ 'unescape' => true ],
			'outsep' => [ 'unescape' => true, 'default' => ', ' ],
			'matchpattern' => [],
			'matchtemplate' => [],
			'mergepattern' => [],
			'mergetemplate' => [],
			'outconj' => [ 'unescape' => true ],
			'pattern' => [],
			'remove' => [],
			'removecs' => [],
			'removesep' => [ 'default' => ',' ],
			'removecs' => [],
			'sortmode' => [],
			'sortoptions' => [],
			'subsort' => [],
			'subsortoptions' => [],
			'template' => [],
			'token' => [ 'unescape' => true ],
			'token1' => [ 'unescape' => true ],
			'token2' => [ 'unescape' => true ],
			'tokensep' => [ 'unescape' => true, 'default' => ',' ],
			'uniquecs' => [],
			'value' => [ 'unescape' => true ]
		];
	}
}
