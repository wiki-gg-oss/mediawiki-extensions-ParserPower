local libraryUtil = require( 'libraryUtil' )

local ParserPowerString = {}
local php

function ParserPowerString.setupInterface( options )
	ParserPowerString.setupInterface = nil
	php = mw_interface
	mw_interface = nil

	mw = mw or {}
	mw.ext = mw.ext or {}
	mw.ext.ParserPower = mw.ext.ParserPower or {}
	mw.ext.ParserPower.string = ParserPowerString

	package.loaded['mw.ext.ParserPower.string'] = ParserPowerString
end

function ParserPowerString.escape( text )
	libraryUtil.checkType( 'escape', 1, text, 'string' )
	return php.escape( text )
end

function ParserPowerString.unescape( text )
	libraryUtil.checkType( 'unescape', 1, text, 'string' )
	return php.unescape( text )
end

return ParserPowerString
