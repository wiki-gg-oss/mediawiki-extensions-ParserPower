<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower;

use MediaWiki\Config\GlobalVarConfig;

/**
 * Configuration class for ParserPower variables.
 */
final class ParserPowerConfig extends GlobalVarConfig {

	/**
	 * Configuration variable prefix.
	 */
	private const PREFIX = 'wgParserPower';

	/**
	 * Builder function.
	 *
	 * @return self A new instance.
	 */
	public static function newInstance(): self {
		return new self();
	}

	public function __construct() {
		parent::__construct( self::PREFIX );
	}
}
