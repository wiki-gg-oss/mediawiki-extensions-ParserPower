<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Hooks;

use MediaWiki\Config\Config;
use MediaWiki\Extension\ParserPower\ListFunctions;
use MediaWiki\Extension\ParserPower\SimpleFunctions;
use MediaWiki\Page\RedirectLookup;
use MediaWiki\Parser\Parser;

final class FunctionRegistrationHooks implements
	\MediaWiki\Hook\ParserFirstCallInitHook
{
	private readonly SimpleFunctions $simpleFunctions;
	private readonly ListFunctions $listFunctions;

	public function __construct(
		Config $config,
		RedirectLookup $redirectLookup
	) {
		$this->simpleFunctions = new SimpleFunctions( $redirectLookup );
		$this->listFunctions = new ListFunctions(
			$config->get( 'ParserPowerLstmapExpansionCompat' )
		);
	}

	/**
	 * Register ParserPower functions when the parser is initialised.
	 *
	 * @param Parser $parser Parser object being initialised
	 * @return void
	 */
	public function onParserFirstCallInit( $parser ) {
		// Simple functions

		$parser->setFunctionHook( 'trim', [ $this->simpleFunctions, 'trimRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'uesc', [ $this->simpleFunctions, 'uescRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'uescnowiki', [ $this->simpleFunctions, 'uescnowikiRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'trimuesc', [ $this->simpleFunctions, 'trimuescRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'linkpage', [ $this->simpleFunctions, 'linkpageRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'linktext', [ $this->simpleFunctions, 'linktextRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setHook( 'linkpage', [ $this->simpleFunctions, 'linkpageTagRender' ] );
		$parser->setHook( 'linktext', [ $this->simpleFunctions, 'linktextTagRender' ] );
		$parser->setHook( 'esc', [ $this->simpleFunctions, 'escTagRender' ] );
		for ( $index = 1; $index < 10; $index++ ) {
			$parser->setHook( 'esc' . $index, [ $this->simpleFunctions, 'escTagRender' ] );
		}
		$parser->setFunctionHook( 'ueif', [ $this->simpleFunctions, 'ueifRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'or', [ $this->simpleFunctions, 'orRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'ueor', [ $this->simpleFunctions, 'ueorRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'ueifeq', [ $this->simpleFunctions, 'ueifeqRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'token', [ $this->simpleFunctions, 'tokenRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'tokenif', [ $this->simpleFunctions, 'tokenifRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'ueswitch', [ $this->simpleFunctions, 'ueswitchRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'follow', [ $this->simpleFunctions, 'followRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'argmap', [ $this->simpleFunctions, 'argmapRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'iargmap', [ $this->simpleFunctions, 'iargmapRender' ], Parser::SFH_OBJECT_ARGS );

		// Do not load if Page Forms is installed.
		if ( !defined( 'PF_VERSION' ) ) {
			$parser->setFunctionHook( 'arraymap', [ $this->simpleFunctions, 'arraymapRender' ],
				Parser::SFH_OBJECT_ARGS );
			$parser->setFunctionHook( 'arraymaptemplate', [ $this->simpleFunctions, 'arraymaptemplateRender' ],
				Parser::SFH_OBJECT_ARGS );
		}

		// List functions

		$parser->setFunctionHook( 'lstcnt', [ $this->listFunctions, 'lstcntRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'lstsep', [ $this->listFunctions, 'lstsepRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'lstelem', [ $this->listFunctions, 'lstelemRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'lstsub', [ $this->listFunctions, 'lstsubRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'lstfnd', [ $this->listFunctions, 'lstfndRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'lstind', [ $this->listFunctions, 'lstindRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'lstapp', [ $this->listFunctions, 'lstappRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'lstprep', [ $this->listFunctions, 'lstprepRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'lstjoin', [ $this->listFunctions, 'lstjoinRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'lstcntuniq', [ $this->listFunctions, 'lstcntuniqRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'listunique', [ $this->listFunctions, 'listuniqueRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'lstuniq', [ $this->listFunctions, 'lstuniqRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'listfilter', [ $this->listFunctions, 'listfilterRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'lstfltr', [ $this->listFunctions, 'lstfltrRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'lstrm', [ $this->listFunctions, 'lstrmRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'listsort', [ $this->listFunctions, 'listsortRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'lstsrt', [ $this->listFunctions, 'lstsrtRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'listmap', [ $this->listFunctions, 'listmapRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'lstmap', [ $this->listFunctions, 'lstmapRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'lstmaptemp', [ $this->listFunctions, 'lstmaptempRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'listmerge', [ $this->listFunctions, 'listmergeRender' ], Parser::SFH_OBJECT_ARGS );
	}
}
