# Release notes

## 1.1.0

This is the first regular 1.1.0 release of Potts Historical Facts.

This release responds to feedback from beta testing and is intended to be suitable for normal installation through webtrees and Custom Module Manager.

### Added

- Added language-aware CSV selection. If a selected collection has a matching CSV for the visitor's webtrees language, that language file is used automatically. For example, the Netherlands collection uses `nl_NL.csv` for Dutch visitors and `en_NL.csv` for other visitors.
- Added support for persistent custom CSV files in the webtrees data folder: `data/modules/potts_historical_facts/data/`.
- Added a settings-page note showing where administrators can place custom CSV files.
- Added translatable module text via webtrees custom translations, with starter translations for Dutch, German, French, Polish and Portuguese.
- Added an update URL using `latest-version.txt` so webtrees and Custom Module Manager can recognise future updates.

### Changed

- Promoted the module from beta/pre-release to a regular stable release.
- The Netherlands collection is now presented as a single collection where the module can choose the best matching language file.
- Custom CSV files in the webtrees data folder take priority over bundled CSV files with the same name.

### Preserved

- Existing multi-collection selection remains available.
- Existing visitor cookies and older single-region settings are still handled where possible.
- Bundled CSV files remain available for all existing regions and collections.
