<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function;

use MediaWiki\Extension\ParserPower\ParameterParser;
use MediaWiki\Page\RedirectLookup;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;
use MediaWiki\Title\Title;

/**
 * Parser function for following page redirects (#follow).
 */
final class FollowFunction extends ParserFunctionBase {

	/**
	 * @param RedirectLookup $redirectLookup
	 */
	public function __construct(
		private readonly RedirectLookup $redirectLookup
	) {
		parent::__construct();
	}

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'follow';
	}

	/**
	 * @inheritDoc
	 */
	public function getParamSpec(): array {
		return [
			0 => [ 'unescape' => true ]
		];
	}

	/**
	 * @inheritDoc
	 */
	public function execute( Parser $parser, PPFrame $frame, ParameterParser $params ): string {
		$text = trim( $params->get( 0 ) );

		$title = Title::newFromText( $text );
		if ( $title === null || $title->getNamespace() === NS_MEDIA || $title->getNamespace() < 0 ) {
			return $text;
		}

		$target = $this->redirectLookup->getRedirectTarget( $title );
		if ( $target === null ) {
			return $text;
		}

		$target = Title::newFromLinkTarget( $target );

		// Replace redirect fragment with the one from the initial text. We need to check whether there is
		// a # with no fragment after it, since it removes the redirect fragment if there is one.
		if ( strpos( $text, '#' ) !== false ) {
			$target = $target->createFragmentTarget( $title->getFragment() );
		}

		return $target->getFullText();
	}
}
