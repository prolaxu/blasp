# Changelog

All notable changes to `blasp` will be documented in this file

## Unreleased

### Added
- Hindi and Nepali language support (`Blasp::hindi()`, `Blasp::nepali()`), each covering Devanagari and romanized text with severity maps
- `DevanagariNormalizer` (chandrabindu → anusvara, precomposed nukta consonants → base, Devanagari digits → ASCII)
- Devanagari substitutions for nukta, short/long vowel signs and common consonant swaps; romanized substitutions for u/oo, i/ee, v/w, z/j, f/ph
- Missing profanities in the English, Spanish, French, German, Hindi, and Nepali lists, including spaced spellings, obfuscated slurs (`fcuk`, `niqqer`), hate terms (`kkk`, `sieg heil`, `neonazi`) and common romanized variants the existing entries did not already match
- `safe_words` list in the installed application's `config/blasp.php` (and in a published language file) for words that must never be flagged
- Homoglyph folding (`blasp.homoglyphs`, on by default): Cyrillic and Greek lookalikes, fullwidth letters and accented letters missing from the substitution table are folded before matching, so `fuсk` and `ｆｕｃｋ` are detected while the original text is what gets masked
- `substitutions_append` config for merging an application's own characters into the default substitution table without copying it
- Default substitutions for `7`/`+` → t, `1` → l and `2` → z
- `symfony/polyfill-intl-normalizer` is now a dependency, so accent folding works without ext-intl

### Fixed
- A space in a phrase entry (`sieg heil`, `blow job`) now matches any separator or none, so `sieg-heil` and `siegheil` are detected
- Regex driver's dot-separator lookahead is Unicode-aware, so a dot before a combining mark (Devanagari vowel signs) no longer breaks a match
- Regex driver now allows separators between letters that have no substitution entry (e.g. Devanagari), so `मा-दर-चोद` is detected like `f-u-c-k`
- Pattern driver word boundaries are now Unicode-aware; `\b` treated Devanagari vowel signs as non-word characters so words ending in a matra never matched
- False-positive word context is now Unicode-aware, so multibyte words like `लंडन` can be allow-listed against `लंड`

## 3.0.0 - 2025-01-05

### Added
- Custom mask character support with `maskWith()` method
- Simplified API with Laravel facade pattern and method chaining
- Comprehensive multi-language support (Spanish, German, French)
- Expanded test coverage across all languages
- Comprehensive extensibility system with full test coverage
- Basic registry pattern for language normalizers
- Language files publishing to ServiceProvider
- Comprehensive documentation for maskWith() and all chainable methods

### Changed
- Implemented dependency injection and simplified service dependencies
- Extracted expression generation logic to dedicated generator
- Improved substitution detection across all languages
- Updated README with simplified chainable API documentation
- Updated README with comprehensive multi-language support documentation
- Updated README with language files publishing options
- Updated README for v3.0 features

### Fixed
- Resolved language switching not loading correct profanities
- Prevented cross-word-boundary profanity matches

### Removed
- Strategy factory, plugin manager, and default detection strategy
- Domain-specific detection strategies (email, URL, phone)
- Unused strict() and lenient() detection modes
- README duplications and outdated references

## 1.0.0 - 201X-XX-XX

- initial release
