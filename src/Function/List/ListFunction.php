<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function\List;

use MediaWiki\Extension\ParserPower\Formatter\BoolFormatter;
use MediaWiki\Extension\ParserPower\Formatter\EnumFormatter;
use MediaWiki\Extension\ParserPower\Formatter\FlagsFormatter;
use MediaWiki\Extension\ParserPower\Formatter\IntFormatter;
use MediaWiki\Extension\ParserPower\ListSorter;
use MediaWiki\Extension\ParserPower\ListUtils;
use MediaWiki\Extension\ParserPower\Parameters;
use MediaWiki\Extension\ParserPower\Function\ParserFunctionBase;

/**
 * Parser function manipulating a list.
 */
abstract class ListFunction extends ParserFunctionBase {

	/**
	 * Flag for reverse index search.
	 */
	public const INDEX_DESC = 1;
	/**
	 * Flag for sensitive index search.
	 */
	public const INDEX_CS = 2;
	/**
	 * Flag for index search returning a negative index.
	 */
	public const INDEX_NEG = 4;

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
	 * @var ?array Base parameter specification for list functions.
	 */
	private static ?array $paramSpec;

	/**
	 * @var ?array Wikitext formatter for case sensitivity options.
	 */
	private static ?BoolFormatter $csFormatter;

	/**
	 * Get a wikitext formatter to decode case sensitivity options.
	 *
	 * @return BoolFormatter A wikitext-to-bool formatter.
	 */
	protected function getCSFormatter(): BoolFormatter {
		self::$csFormatter ??= new BoolFormatter( 'cs', 'ncs' );
		return self::$csFormatter;
	}

	/**
	 * @inheritDoc
	 */
	public function getParamSpec(): array {
		self::$paramSpec ??= [
			'counttoken' => [
				'unescape' => true
			],
			'csoption' => [
				'formatter' => $this->getCSFormatter()
			],
			'default' => [
				'unescape' => true
			],
			'duplicates' => [
				'formatter' => new EnumFormatter( [
					'keep'          => 0,
					'strip'         => self::DUPLICATES_STRIP | self::DUPLICATES_POSTSTRIP,
					'prestrip'      => self::DUPLICATES_PRESTRIP,
					'poststrip'     => self::DUPLICATES_POSTSTRIP,
					'pre/poststrip' => self::DUPLICATES_PRESTRIP | self::DUPLICATES_POSTSTRIP
				] )
			],
			'fieldsep' => [
				'unescape' => true
			],
			'keep' => [],
			'keepcs' => [
				'formatter' => BoolFormatter::getBase()
			],
			'keepsep' => [
				'default' => ','
			],
			'index' => [
				'unescape' => true,
				'formatter' => new IntFormatter()
			],
			'indexoptions' => [
				'formatter' => new FlagsFormatter( [
					'neg'  => [ 'include' => self::INDEX_NEG ],
					'pos'  => [ 'exclude' => self::INDEX_NEG ],
					'cs'   => [ 'include' => self::INDEX_CS ],
					'ncs'  => [ 'exclude' => self::INDEX_CS ],
					'desc' => [ 'include' => self::INDEX_DESC ],
					'asc'  => [ 'exclude' => self::INDEX_DESC ]
				] )
			],
			'indextoken' => [
				'unescape' => true
			],
			'insep' => [
				'unescape' => true,
				'default' => ','
			],
			'intro' => [
				'unescape' => true
			],
			'length' => [
				'unescape' => true,
				'formatter' => new IntFormatter(),
				'default' => PHP_INT_MAX
			],
			'list' => [],
			'outro' => [
				'unescape' => true
			],
			'outsep' => [
				'unescape' => true,
				'default' => ', '
			],
			'outconj' => [
				'unescape' => true
			],
			'pattern' => [],
			'remove' => [],
			'removecs' => [
				'formatter' => BoolFormatter::getBase()
			],
			'removesep' => [
				'default' => ','
			],
			'sortmode' => [
				'formatter' => new EnumFormatter( [
					'nosort'       => 0,
					'sort'         => self::SORTMODE_COMPAT,
					'presort'      => self::SORTMODE_PRE,
					'postsort'     => self::SORTMODE_POST,
					'pre/postsort' => self::SORTMODE_PRE | self::SORTMODE_POST
				] )
			],
			'sortoptions' => [
				'formatter' => new FlagsFormatter( [
					'numeric' => [ 'include' => ListSorter::NUMERIC ],
					'alpha'   => [ 'exclude' => ListSorter::NUMERIC ],
					'cs'      => [ 'include' => ListSorter::CASE_SENSITIVE ],
					'ncs'     => [ 'exclude' => ListSorter::CASE_SENSITIVE ],
					'desc'    => [ 'include' => ListSorter::DESCENDING ],
					'asc'     => [ 'exclude' => ListSorter::DESCENDING ]
				] )
			],
			'subsort' => [
				'formatter' => BoolFormatter::getBase()
			],
			'subsortoptions' => [
				'formatter' => new FlagsFormatter( [
					'numeric' => [ 'include' => ListSorter::NUMERIC ],
					'alpha'   => [ 'exclude' => ListSorter::NUMERIC ],
					'cs'      => [ 'include' => ListSorter::CASE_SENSITIVE ],
					'ncs'     => [ 'exclude' => ListSorter::CASE_SENSITIVE ],
					'desc'    => [ 'include' => ListSorter::DESCENDING ],
					'asc'     => [ 'exclude' => ListSorter::DESCENDING ]
				] )
			],
			'template' => [],
			'token' => [
				'unescape' => true
			],
			'tokensep' => [
				'unescape' => true,
				'default' => ','
			],
			'uniquecs' => [
				'formatter' => BoolFormatter::getBase()
			],
			'value' => [
				'unescape' => true
			]
		];
		return self::$paramSpec;
	}

	/**
	 * Implode an output list.
	 *
	 * @param Parameters $params Parser function parameters.
	 * @param array $values Array to implode.
	 * @return string The imploded list.
	 */
	protected function implodeOutList( Parameters $params, array $values ): string {
		$count = count( $values );

		if ( $count > 1 ) {
			$sep = $params->get( 'outsep' );
			if ( $params->isDefined( 'outconj' ) ) {
				$conj = $params->get( 'outconj' );
				if ( $conj !== $sep ) {
					$conj = ' ' . trim( $conj ) . ' ';
				}
			}
		}
		$list = ListUtils::implode( $values, $sep ?? '', $conj ?? null );

		$countToken = $params->get( 'counttoken' );
		$intro = $params->get( 'intro' );
		$outro = $params->get( 'outro' );
		$list = ListUtils::applyIntroAndOutro( $intro, $list, $outro, $countToken, $count );

		return $list;
	}
}
