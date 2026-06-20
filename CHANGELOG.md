# Changelog

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
