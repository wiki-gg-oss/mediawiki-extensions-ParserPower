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
* Added `parserpower-invalid-args-category` system message. It defines a tracking category added to pages using ParserPower parser functions with an invalid parameter, either numbered or named.
* …

### 1.7.1 (2025-06-27)
* Fixed `#listmap` not properly setting `outconj` when the input list contains 2 values.
* The `Pages using duplicate arguments in ParserPower functions` category is now listed at `Special:TrackingCategories`.

### 1.7.0 (2025-06-19)
* Added `outconj` optional parameter to `#listmap`. If defined, its value is unescaped then trimmed, and will be used as delimiter for the last 2 output list values.
* List functions now evaluate most of their parameters lazily. Parameter evaluation order may have changed, and side effects may no longer be applied inside unused parameters.
* `#listmerge` no longer ignores `mergetemplate` or `matchtemplate` if the other one is unspecified.
* Added `parserpower-duplicate-args-category` system message. It defines a tracking category added to pages using ParserPower parser functions with a same numbered and/or named parameter defined multiple times.
* `#listunique` now removes `nowiki` strip markers from its `insep` parameter.
* Added `parserpower-error` system message for parser function error message formatting, along with one sub-message per error type.
* `#argmap` and `#iargmap` now return an error if their formatter or n parameter is specified but empty.
* Fixed `#argmap` and `#iargmap` evaluating frame arguments twice before passing them to the formatter.

### 1.6.1 (2025-04-27)
* Resolved exceptions and undefined variable warnings being thrown when using `#lstmap`, `#listmerge`, `lstmaptemp`.
* Added new configuration variable `$wgParserPowerLstmapExpansionCompat`. If set to `true`, `#lstmap` will not evaluate the token or pattern before replacement, in order to maintain compatibility with ParserPower versions 1.3 and older.