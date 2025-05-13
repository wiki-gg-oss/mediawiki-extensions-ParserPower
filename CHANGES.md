# ParserPower version changelog

## About

ParserPower uses [semantic versioning](https://semver.org/): major.minor.patch.

## Versioning

Add new entries to the top of the 1.x.x-NEXT section.

## On release

1. Rename 1.x.x-NEXT to the final version number, set date, and create a new 1.x.x-NEXT section.
2. Update version in extension.json.

## Versions

### 1.x.x-NEXT (YYYY-MM-DD)
* Added `outconj` optional parameter to `#listmap`. If defined, its value is unescaped then trimmed, and will be used as delimiter for the last 2 output list values.
* List functions now evaluate most of their parameters lazily. Parameter evaluation order may have changed, and side effects may no longer be applied inside unused parameters.
* …

### 1.6.1 (2025-04-27)
* Resolved exceptions and undefined variable warnings being thrown when using `#lstmap`, `#listmerge`, `lstmaptemp`.
* Added new configuration variable `$wgParserPowerLstmapExpansionCompat`. If set to `true`, `#lstmap` will not evaluate the token or pattern before replacement, in order to maintain compatibility with ParserPower versions 1.3 and older.