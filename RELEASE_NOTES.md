# Release notes

## 1.1.4

- Corrected Julian calendar handling for historical age and lifespan comparisons, including Julian leap years.
- Preserved original displayed dates and explicit calendar markers while using Gregorian-equivalent comparison dates internally.
- Kept unmarked and ISO dates on their existing Gregorian interpretation; no calendar is inferred from country or region.
- Includes the 1.1.3 Italian localisation improvements and earlier 1.1.1–1.1.2 provider, ISO-date and Gramps-compatible filename support already merged to main.

### Release status

Version 1.1.4 is currently development code on `main` and is **not yet a published GitHub release**. Until it is packaged, syntax-checked, live-tested and published, `latest-version.txt` must continue to advertise the most recent published release.

### Validation and upgrade

Before publishing 1.1.4, package the module with `potts_historical_facts` as the module root, run PHP syntax checks against the supported PHP versions, test the release on webtrees 2.2.x, and confirm the historical-event/calendar behaviour on a live test installation. Persistent CSV files and settings must remain outside the packaged module and be retained across upgrade.

The broader regional calendar-source audit (#7) remains open and is not implied to be complete by the Julian calculation fix.
