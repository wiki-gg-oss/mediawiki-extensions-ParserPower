<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function\List;

use MediaWiki\Extension\ParserPower\Formatter\BoolFormatter;
use MediaWiki\Extension\ParserPower\Formatter\EnumFormatter;
use MediaWiki\Extension\ParserPower\Function\ParserFunctionBase;

/**
 * Parser function manipulating a list.
 */
abstract class ListFunction extends ParserFunctionBase {

	/**
	 * Flags for duplicate removal in lists.
	 */
	public const DUPLICATES_STRIP = 1;
	public const DUPLICATES_PRESTRIP = 2;
	public const DUPLICATES_POSTSTRIP = 4;

	/**
	 * Flags for item sort mode in lists.
	 */
	public const SORTMODE_PRE = 1;
	public const SORTMODE_POST = 2;
	public const SORTMODE_COMPAT = 4;

	/**
	 * @inheritDoc
	 */
	public function getParamSpec(): array {
		return [
			'counttoken' => [ 'unescape' => true ],
			'csoption' => [ 'formatter' => new BoolFormatter( 'cs', 'ncs' ) ],
			'default' => [ 'unescape' => true ],
			'duplicates' => [
				'formatter' => new EnumFormatter( [
					'keep'          => 0,
					'strip'         => self::DUPLICATES_STRIP | self::DUPLICATES_POSTSTRIP,
					'prestrip'      => self::DUPLICATES_PRESTRIP,
					'poststrip'     => self::DUPLICATES_POSTSTRIP,
					'pre/poststrip' => self::DUPLICATES_PRESTRIP | self::DUPLICATES_POSTSTRIP
				] )
			],
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
			'sortmode' => [
				'formatter' => new EnumFormatter( [
					'nosort'       => 0,
					'sort'         => self::SORTMODE_COMPAT,
					'presort'      => self::SORTMODE_PRE,
					'postsort'     => self::SORTMODE_POST,
					'pre/postsort' => self::SORTMODE_PRE | self::SORTMODE_POST
				] )
			],
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
