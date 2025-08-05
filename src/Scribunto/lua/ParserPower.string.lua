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

return ParserPowerString
