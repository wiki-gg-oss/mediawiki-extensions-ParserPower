<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Hooks;

use MediaWiki\Config\Config;
use MediaWiki\Extension\ParserPower\EscTag;
use MediaWiki\Extension\ParserPower\ListFunctions;
use MediaWiki\Parser\Parser;
use Wikimedia\ObjectFactory\ObjectFactory;
use MediaWiki\Extension\ParserPower\Function\ArgMapFunction;
use MediaWiki\Extension\ParserPower\Function\FollowFunction;
use MediaWiki\Extension\ParserPower\Function\IArgMapFunction;
use MediaWiki\Extension\ParserPower\Function\LinkPageFunction;
use MediaWiki\Extension\ParserPower\Function\LinkTextFunction;
use MediaWiki\Extension\ParserPower\Function\List\ListFilterFunction;
use MediaWiki\Extension\ParserPower\Function\List\LstAppFunction;
use MediaWiki\Extension\ParserPower\Function\List\LstCntFunction;
use MediaWiki\Extension\ParserPower\Function\List\LstElemFunction;
use MediaWiki\Extension\ParserPower\Function\List\LstFltrFunction;
use MediaWiki\Extension\ParserPower\Function\List\LstFndFunction;
use MediaWiki\Extension\ParserPower\Function\List\LstIndFunction;
use MediaWiki\Extension\ParserPower\Function\List\LstJoinFunction;
use MediaWiki\Extension\ParserPower\Function\List\LstPrepFunction;
use MediaWiki\Extension\ParserPower\Function\List\LstRmFunction;
use MediaWiki\Extension\ParserPower\Function\List\LstSepFunction;
use MediaWiki\Extension\ParserPower\Function\List\LstSubFunction;
use MediaWiki\Extension\ParserPower\Function\OrFunction;
use MediaWiki\Extension\ParserPower\Function\PageForms\ArrayMapFunction;
use MediaWiki\Extension\ParserPower\Function\PageForms\ArrayMapTemplateFunction;
use MediaWiki\Extension\ParserPower\Function\TokenFunction;
use MediaWiki\Extension\ParserPower\Function\TokenIfFunction;
use MediaWiki\Extension\ParserPower\Function\TrimFunction;
use MediaWiki\Extension\ParserPower\Function\TrimUescFunction;
use MediaWiki\Extension\ParserPower\Function\UeIfeqFunction;
use MediaWiki\Extension\ParserPower\Function\UeIfFunction;
use MediaWiki\Extension\ParserPower\Function\UeOrFunction;
use MediaWiki\Extension\ParserPower\Function\UescFunction;
use MediaWiki\Extension\ParserPower\Function\UescNowikiFunction;
use MediaWiki\Extension\ParserPower\Function\UeSwitchFunction;

final class FunctionRegistrationHooks implements
	\MediaWiki\Hook\ParserFirstCallInitHook
{
	private readonly ListFunctions $listFunctions;
	private array $functions;
	private EscTag $escTag;

	private const SIMPLE_FUNCTIONS = [
		ArgMapFunction::class,
		[
			'class' => FollowFunction::class,
			'services' => [ 'RedirectLookup' ]
		],
		IArgMapFunction::class,
		LinkPageFunction::class,
		LinkTextFunction::class,
		OrFunction::class,
		TokenFunction::class,
		TokenIfFunction::class,
		TrimFunction::class,
		TrimUescFunction::class,
		UeIfeqFunction::class,
		UeIfFunction::class,
		UeOrFunction::class,
		UescFunction::class,
		UescNowikiFunction::class,
		UeSwitchFunction::class
	];

	private const PAGE_FORMS_FUNCTIONS = [
		ArrayMapFunction::class,
		ArrayMapTemplateFunction::class
	];

	private const LIST_FUNCTIONS = [
		ListFilterFunction::class,
		LstAppFunction::class,
		LstCntFunction::class,
		LstElemFunction::class,
		LstFltrFunction::class,
		LstFndFunction::class,
		LstIndFunction::class,
		LstJoinFunction::class,
		LstPrepFunction::class,
		LstRmFunction::class,
		LstSepFunction::class,
		LstSubFunction::class
	];

	public function __construct(
		Config $config,
		private ObjectFactory $objectFactory
	) {
		$this->listFunctions = new ListFunctions(
			$config->get( 'ParserPowerLstmapExpansionCompat' )
		);

		$this->functions = [];
		$this->addFunctions( self::SIMPLE_FUNCTIONS );
		// Do not load if Page Forms is installed.
		if ( !defined( 'PF_VERSION' ) ) {
			$this->addFunctions( self::PAGE_FORMS_FUNCTIONS );
		}
		$this->addFunctions( self::LIST_FUNCTIONS );

		$this->escTag = new EscTag();
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

		// Tags
		$parser->setHook( 'linkpage', [ LinkPageFunction::class, 'tagRender' ] );
		$parser->setHook( 'linktext', [ LinkTextFunction::class, 'tagRender' ] );
		foreach ( $this->escTag->getNames() as $escTagName ) {
			$parser->setHook( $escTagName, [ $this->escTag, 'render' ] );
		}

		// List functions

		$parser->setFunctionHook( 'lstcntuniq', [ $this->listFunctions, 'lstcntuniqRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'listunique', [ $this->listFunctions, 'listuniqueRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'lstuniq', [ $this->listFunctions, 'lstuniqRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'listsort', [ $this->listFunctions, 'listsortRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'lstsrt', [ $this->listFunctions, 'lstsrtRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'listmap', [ $this->listFunctions, 'listmapRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'lstmap', [ $this->listFunctions, 'lstmapRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'lstmaptemp', [ $this->listFunctions, 'lstmaptempRender' ], Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'listmerge', [ $this->listFunctions, 'listmergeRender' ], Parser::SFH_OBJECT_ARGS );
	}
}
