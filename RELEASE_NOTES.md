# Release notes

## 1.1.3

- Added Italian module settings and History-selector translations.
- Italian-language collections now use Età, Fonte, circa and singular/plural anno/anni, mese/mesi and giorno/giorni.
- Prevented duplicate English ages from Potts Fact Ages on historical events with a localised age label.
- Preserved English Italy content and existing language-aware CSV selection. An administrator-supplied it_IT.csv is supported; no translated Italian historical dataset is bundled.

### Validation and upgrade

The maintainer accepted live checks showing Italian event ages, Fonte links and removal of duplicate English ages. Italian settings-screen wording was not separately verified on the live site. Automated PHP and DOM checks cover translation keys, placeholders, age forms, source handling, language selection and the duplicate-age guard.

Back up the existing module, replace matching files in modules_v4/potts_historical_facts and clear the webtrees cache. Persistent CSV files and settings are retained. Remove synthetic test CSV files after testing. The calendar audit (#7) remains open.
