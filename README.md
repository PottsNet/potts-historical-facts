# Potts Historical Facts for webtrees

Potts Historical Facts displays sourced regional history alongside an individual's life events in webtrees 2.2.x.

## Features

- Includes historical datasets for regions, countries and broader historical collections.
- Lets visitors choose one or more historical fact collections independently of the webtrees display language.
- Uses matching language-specific CSV files where available. For example, the Netherlands collection uses `nl_NL.csv` when the visitor is using Dutch and `en_NL.csv` for other languages.
- Provides an optional History selector in the site header and a tree-homepage block.
- Filters events to the individual's lifetime.
- Handles events that overlap a lifetime even when the event began before the person was born.
- Applies a configurable maximum lifespan when no death date is recorded.
- Optionally displays the person's age at each historical event.
- Provides source links for every bundled event.
- Stores administration settings in webtrees so upgrades do not overwrite them.
- Supports custom CSV collections stored in the webtrees data folder so local files are retained during module upgrades.
- Provides translatable module text through webtrees custom translations.
- Supports keyboard navigation and Escape handling in the header selector.
- Provides update information for webtrees and Custom Module Manager through `latest-version.txt`.

## Requirements

- webtrees 2.2.x
- PHP 8.3 or later

## Installation

1. Download and extract the release ZIP.
2. Upload the `potts_historical_facts` folder to `modules_v4`.
3. In webtrees, go to **Control panel > Modules > All modules**.
4. Enable **Potts Historical Facts**.
5. Open the module settings and choose the available collections and the site default collection or collections.
6. Optionally enable the tree block and add it to the tree homepage.

The final module path should be:

`modules_v4/potts_historical_facts/module.php`

## Settings

The administration page provides settings for:

- Available historical fact collections
- Default historical fact collections
- Persistent custom CSV file location
- Header History selector
- Historical-event age labels
- Maximum assumed lifespan for individuals without a death date

Visitor choices are stored in the `potts_history_collections` browser cookie for one year. Selecting **Site default** removes the cookie. The module also honours the older `potts_history_region` cookie so existing visitors are not broken by the upgrade.

## Regional data

Bundled CSV files are stored in `resources/data` and use semicolon-separated columns:

`#date;end_date;event_text;link;category`

Dates use GEDCOM-style English month abbreviations and four-digit CE years. Supported forms include `1867`, `JAN 1867`, `26 JAN 1867` and optional start/end ranges.

Every bundled row includes an HTTP(S) source link. Administrators editing or adding datasets should retain the same five-column structure and use valid web addresses.

## Adding your own CSV files

To add your own historical fact collections, place CSV files in this persistent webtrees data folder:

`data/modules/potts_historical_facts/data/`

The actual path is shown on the module settings page. The module attempts to create the folder automatically when the settings page is opened.

Files in this data-folder location are not replaced when the module is upgraded. If a custom CSV has the same filename as a bundled CSV, the custom file takes priority.

Filename examples:

- `en_AU.csv`
- `en_NL.csv`
- `nl_NL.csv`
- `de_DE.csv`

Where matching language-specific files exist for the same region, the module uses the file that best matches the visitor's selected language. For example, if the Netherlands collection is selected, a visitor using Dutch will receive `nl_NL.csv`; other visitors will receive `en_NL.csv` where available.

## Compatibility

Potts Historical Facts works with standard webtrees themes. Potts Modern adds complementary presentation styling but is not required.

If Potts Fact Ages already supplies historical-event ages, turn off **Show ages on historical events** in this module to avoid presenting the same information twice.

## Known limitations

- BCE dates and years with fewer than four digits are not currently supported.
- Historical data is maintained as CSV files and should include reliable source links.
- Broad collections such as Europe, World events and Austro-Hungarian Empire intentionally overlap with some country collections.

## Licence

GPL-3.0-or-later. See `LICENSE`.

## Support

Report bugs and feature requests through GitHub Issues and include your webtrees version, PHP version, selected theme and screenshots where useful.
