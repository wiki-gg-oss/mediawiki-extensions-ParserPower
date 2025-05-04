<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Hooks;

use MediaWiki\Config\Config;
use MediaWiki\Extension\ParserPower\ListFunctions;
use MediaWiki\Extension\ParserPower\SimpleFunctions;
use MediaWiki\Parser\Parser;
use Wikimedia\ObjectFactory\ObjectFactory;
use MediaWiki\Extension\ParserPower\Function\FollowFunction;
use MediaWiki\Extension\ParserPower\Function\LinkPageFunction;
use MediaWiki\Extension\ParserPower\Function\LinkTextFunction;
use MediaWiki\Extension\ParserPower\Function\TrimFunction;
use MediaWiki\Extension\ParserPower\Function\TrimUescFunction;
use MediaWiki\Extension\ParserPower\Function\UescFunction;
use MediaWiki\Extension\ParserPower\Function\UescNowikiFunction;

final class FunctionRegistrationHooks implements
	\MediaWiki\Hook\ParserFirstCallInitHook
{
	private readonly SimpleFunctions $simpleFunctions;
	private readonly ListFunctions $listFunctions;
	private array $functions;

	private const SIMPLE_FUNCTIONS = [
		[
			'class' => FollowFunction::class,
			'services' => [ 'RedirectLookup' ]
		],
		LinkPageFunction::class,
		LinkTextFunction::class,
		TrimFunction::class,
		TrimUescFunction::class,
		UescFunction::class,
		UescNowikiFunction::class
	];

	public function __construct(
		Config $config,
		private ObjectFactory $objectFactory
	) {
		$this->simpleFunctions = new SimpleFunctions();
		$this->listFunctions = new ListFunctions(
			$config->get( 'ParserPowerLstmapExpansionCompat' )
		);

		$this->functions = [];
		$this->addFunctions( self::SIMPLE_FUNCTIONS );
	}

	/**
	 * Add a list of parser functions.
	 *
	 * @param array $functionSpecs List of parser function class names or specifications.
	 */
	private function addFunctions( array $functionSpecs ) {
		foreach ( $functionSpecs as $functionSpec ) {
			$this->addFunction( $functionSpec );
		}
	}

	/**
	 * Add a parser function.
	 *
	 * @param string|array $functionSpec Parser function class name or specification.
	 */
	private function addFunction( string|array $functionSpec ) {
		if ( is_string( $functionSpec ) ) {
			$functionSpec = [ 'class' => $functionSpec ];
		}

		$this->functions[] = $this->objectFactory->createObject(
			$functionSpec,
			[ 'assertClass' => $functionSpec['class'] ]
		);
	}

	/**
	 * Register ParserPower functions when the parser is initialised.
	 *
	 * @param Parser $parser Parser object being initialised
	 * @return void
	 */
	public function onParserFirstCallInit( $parser ) {
		foreach ( $this->functions as $function ) {
			$parser->setFunctionHook( $function->getName(), [ $function, 'render' ], Parser::SFH_OBJECT_ARGS );
		}

		// Simple functions

		$parser->setHook( 'linkpage', [ LinkPageFunction::class, 'tagRender' ] );
		$parser->setHook( 'linktext', [ LinkTextFunction::class, 'tagRender' ] );
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
