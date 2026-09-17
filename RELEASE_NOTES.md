# Release notes

## 1.1.4

- Corrected Julian calendar handling for historical age and lifespan comparisons, including Julian leap years.
- Preserved original displayed dates and explicit calendar markers while using Gregorian-equivalent comparison dates internally.
- Kept unmarked and ISO dates on their existing Gregorian interpretation; no calendar is inferred from country or region.
- Includes the 1.1.3 Italian localisation improvements and earlier 1.1.1–1.1.2 provider, ISO-date and Gramps-compatible filename support already merged to main.

### Validation

- The Julian-calendar correction was tested on the maintainer's webtrees site before PR #23 was merged on 11 September 2026.
- The 1.1.4 release workflow syntax-checks every PHP file with PHP 8.4 before publishing.
- The generated ZIP is verified to contain `potts_historical_facts` as the module root and the 1.1.4 `module.php`.

### Upgrade

Extract the release ZIP and replace the existing `modules_v4/potts_historical_facts` directory. Persistent custom CSV files stored in the webtrees data folder and settings stored by webtrees are outside the packaged module and are retained across upgrade.

The broader regional calendar-source audit (#7) remains open and is not implied to be complete by the Julian calculation fix.
