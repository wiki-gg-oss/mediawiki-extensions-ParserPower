<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function\List;

use MediaWiki\Extension\ParserPower\Formatter\BoolFormatter;
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
			'csoption' => [ 'formatter' => new BoolFormatter( 'cs', 'ncs' ) ],
			'default' => [ 'unescape' => true ],
			'duplicates' => [],
			'fieldsep' => [ 'unescape' => true ],
			'keep' => [],
			'keepcs' => [ 'formatter' => BoolFormatter::getBase() ],
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
			'removecs' => [ 'formatter' => BoolFormatter::getBase() ],
			'removesep' => [ 'default' => ',' ],
			'sortmode' => [],
			'sortoptions' => [],
			'subsort' => [ 'formatter' => BoolFormatter::getBase() ],
			'subsortoptions' => [],
			'template' => [],
			'token' => [ 'unescape' => true ],
			'tokensep' => [ 'unescape' => true, 'default' => ',' ],
			'uniquecs' => [ 'formatter' => BoolFormatter::getBase() ],
			'value' => [ 'unescape' => true ]
		];
	}
}
