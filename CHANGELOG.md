# Changelog

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
