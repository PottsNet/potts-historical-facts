# Changelog

## 1.1.3

- Added Italian module settings and History-selector translations.
- Italian-language collections now use Età, Fonte, circa and singular/plural anno/anni, mese/mesi and giorno/giorni.
- Prevented duplicate English ages from Potts Fact Ages on historical events with a localised age label.
- Preserved English Italy content and existing language-aware CSV selection. An administrator-supplied it_IT.csv is supported; no translated Italian historical dataset is bundled.

## 1.1.2

- Added Gregorian ISO dates (`YYYY`, `YYYY-MM`, `YYYY-MM-DD`) alongside existing GEDCOM dates. Month/day values are validated and converted to GEDCOM before event generation and Biography output.
- Added separately selectable standard Gramps filenames such as `de_DE_data_v1_0.csv`, preserving the full collection identifier and persistent-file precedence.
- Retained support for `#` comment lines. Metadata interpretation, `Today` and event IDs are not part of this change.
- Fixed category headings absorbing or repeating the age in Potts Modern.
- Included the live 1.1.1 Biography provider while preserving main's German collection, calendar annotations, data corrections, settings breadcrumbs and compact History button.

## 1.1.1

### Added

- Added a public, read-only historical-row provider for Potts Biography.
- The provider honours enabled collections, site defaults, visitor cookie choices, language-specific CSV selection and persistent custom data files.

### Preserved

- Standalone historical-event tabs, selectors, age labels and existing visitor preferences are unchanged.
- Potts Biography remains optional and is not required to use this module.

## 1.1.0

- Promoted Potts Historical Facts from beta/pre-release to a regular release.
- Added language-aware CSV selection for matching regional files, such as `en_NL.csv` and `nl_NL.csv`.
- Added support for persistent custom CSV files in `data/modules/potts_historical_facts/data/`.
- Added settings-page guidance for custom CSV files.
- Added translatable module text with starter translations for Dutch, German, French, Polish and Portuguese.
- Added a `latest-version.txt` update URL so webtrees and Custom Module Manager can detect updates.

## 1.1.0-beta.3

- Added support for selecting multiple historical fact collections at once.
- Added administrator controls to choose which collections are available to visitors.
- Changed the homepage block and header selector from single-region selection to multi-collection selection.
- Preserved compatibility with older `potts_history_region` visitor cookies and the previous default region setting.
- Merged selected CSV files, removed duplicate events and sorted results chronologically.
- Added new bundled collections for Austria, Hungary, Czech lands, Slovakia, Poland, Austro-Hungarian Empire, Europe and World events.


## 1.1.0-beta.2

- Fixed the global History selector placement when webtrees is displayed in translated languages such as Dutch.
- Added translated History button labels for common interface languages.
- Avoided falling back into the main genealogy navigation when the Language menu cannot be detected.
- Added more stable classes for themes to style the selector inside utility navigation.

## 1.1.0-beta.1

- Added persistent administration settings for the default region, global selector, event ages and maximum assumed lifespan.
- Corrected lifetime filtering for people without a recorded death date.
- Retained historical ranges that overlap an individual's lifetime.
- Added an option to disable built-in historical-event ages.
- Improved header placement without depending solely on the English word `Language`.
- Added keyboard navigation, focus handling and Escape behaviour to the global selector.
- Canonicalised the duplicate Dutch region code.
- Validated source links and made CSV parsing explicit for PHP 8.4 compatibility.
- Replaced the generic source-link caption with `Source`, or `Bron` for Dutch datasets.
- Prepared documentation and packaging for public beta testing.

## 1.0.12

- Added stable markup for historical event titles and ages.

## 1.0.11

- Improved vertical alignment of the global History selector.

## 1.0.10

- Added the global region selector and made region selection independent of website language.
