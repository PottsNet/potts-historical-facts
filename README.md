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
- Provides a public, read-only historical-row interface for optional use by Potts Biography.

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

Dates accept Gregorian ISO forms `1867`, `1867-01` and `1867-01-26`, as well as existing GEDCOM-style English month forms `JAN 1867` and `26 JAN 1867`. Both date columns accept these forms. ISO month and day values are validated and converted to GEDCOM before historical events or Biography rows are produced; year/month precision is preserved. Lines beginning with `#` are ignored.

Version 1.1.2 also accepts standard Gramps filenames such as `de_DE_data_v1_0.csv` and `da_DK_data_v1_0.csv` in the persistent data folder. Keep the conventional filename casing. Each is a separate selectable collection, labelled with its region, language and format version. It does not replace or merge with `de_DE.csv` or `en_DE.csv`, and is not automatically substituted when a visitor changes language. A persistent file overrides a bundled file with the same full filename. Existing short filenames and language-aware selections continue to work. Metadata lines are ignored; metadata interpretation, event IDs and `Today` are not added by this build.

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

## Potts Biography integration

Version 1.1.1 adds a public provider used by Potts Biography 1.0.0-rc.5 and later. The provider respects this module's enabled collections, site defaults, visitor selections, language-aware CSV choice and persistent custom data. No genealogy data is sent outside webtrees and neither module requires the other to be installed.

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

Version 1.1.2 also keeps historical category headings separate from their age labels when Potts Modern builds its event title panels.

## Italian localisation (1.1.3)

Italian collections (`it_IT.csv`, or standard `it_IT_data_v1_0.csv`) use Età, Fonte, circa, anno/anni, mese/mesi and giorno/giorni. The module settings and History selector include Italian translations when the website language is Italian. Existing English Italy content (`en_IT.csv`) is retained unchanged; translated Italian event text must be supplied in a separate Italian CSV. Existing language-aware selection automatically uses an available `it_IT.csv` for Italian visitors selecting Italy. Standard Gramps files remain separate selections.

Historical age labels are identified explicitly for Potts Fact Ages, preventing a second English age on Italian collections. Any already-added Fact Ages badge is removed only in a cell with a Historical Facts age label.

## Calendar calculations — 1.1.4

Explicit Julian dates are validated using Julian leap years and converted internally to Gregorian-equivalent comparison dates. Displayed CSV/GEDCOM values and their calendar markers are retained. Unmarked dates and ISO dates retain Gregorian interpretation; no calendar is inferred from country or region. Webtrees date-object fallbacks use absolute Julian day numbers when available. No historical dataset annotations are changed.
