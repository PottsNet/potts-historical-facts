# Potts Historical Facts for webtrees

Potts Historical Facts displays sourced regional history alongside an individual's life events in webtrees 2.2.x.

## Features

- Includes historical datasets for 19 regions and countries.
- Lets visitors choose a region independently of the webtrees display language.
- Provides an optional History selector in the site header and a tree-homepage block.
- Filters events to the individual's lifetime.
- Handles events that overlap a lifetime even when the event began before the person was born.
- Applies a configurable maximum lifespan when no death date is recorded.
- Optionally displays the person's age at each historical event.
- Provides source links for every bundled event.
- Stores administration settings in webtrees so upgrades do not overwrite them.
- Supports keyboard navigation and Escape handling in the header selector.

## Requirements

- webtrees 2.2.x
- PHP 8.3 or later

## Installation

1. Download and extract the release ZIP.
2. Upload the `potts_historical_facts` folder to `modules_v4`.
3. In webtrees, go to **Control panel > Modules > All modules**.
4. Enable **Potts Historical Facts**.
5. Open the module settings and choose the site default region.
6. Optionally enable the tree block and add it to the tree homepage.

The final module path should be:

`modules_v4/potts_historical_facts/module.php`

## Settings

The administration page provides settings for:

- Default historical region
- Header History selector
- Historical-event age labels
- Maximum assumed lifespan for individuals without a death date

Visitor choices are stored in the `potts_history_region` browser cookie for one year. Selecting **Site default** removes the cookie.

## Regional Data

CSV files are stored in `resources/data` and use semicolon-separated columns:

`#date;end_date;event_text;link;category`

Dates use GEDCOM-style English month abbreviations and four-digit CE years. Supported forms include `1867`, `JAN 1867`, `26 JAN 1867` and optional start/end ranges.

Every bundled row includes an HTTP(S) source link. Administrators editing or adding datasets should retain the same five-column structure and use valid web addresses.

## Compatibility

Potts Historical Facts works with standard webtrees themes. Potts Modern adds complementary presentation styling but is not required.

If Potts Fact Ages already supplies historical-event ages, turn off **Show ages on historical events** in this module to avoid presenting the same information twice.

## Known Limitations

- BCE dates and years with fewer than four digits are not currently supported.
- Interface text is currently supplied in English.
- Historical data is maintained as bundled CSV files; preserve local CSV changes before replacing the module folder.

## Licence

GPL-3.0-or-later. See `LICENSE`.

## Support

Report bugs and feature requests through GitHub Issues and include your webtrees version, PHP version, selected theme and screenshots where useful.
