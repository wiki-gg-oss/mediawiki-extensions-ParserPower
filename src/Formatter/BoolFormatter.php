<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Formatter;

/**
 * Wikitext formatter for decoding a boolean from wikitext.
 */
final class BoolFormatter extends WikitextFormatter {

	/**
	 * @var array Mapping of keywords to boolean values.
	 */
	private array $values;

	/**
	 * @var ?BoolFormatter Default boolean formatter, lazily initialized.
	 */
	private static ?BoolFormatter $baseFormatter;

	/**
	 * Get the default boolean formatter.
	 *
	 * @return BoolFormatter A boolean formatter recognizing yes/no keywords.
	 */
	public static function getBase(): BoolFormatter {
		self::$baseFormatter ??= new BoolFormatter( 'yes', 'no' );
		return self::$baseFormatter;
	}

	/**
	 * @param string|array $trueValues Value(s) to decode to true.
	 * @param string|array $falseValues Value(s) to decode to false.
	 */
	public function __construct( string|array $trueValues, string|array $falseValues = [] ) {
		if ( is_string( $trueValues ) ) {
			$trueValues = [ $trueValues ];
		}
		if ( is_string( $falseValues ) ) {
			$falseValues = [ $falseValues ];
		}

		foreach ( $trueValues as $trueValue ) {
			$this->values[$trueValue] = true;
		}
		foreach ( $falseValues as $falseValue ) {
			$this->values[$falseValue] = false;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function format( string $text, bool $default = false ): bool {
		$value = strtolower( $text );
		return $this->values[$value] ?? $default;
	}
}
