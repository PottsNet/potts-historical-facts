# Release notes

## 1.1.2

- Added Gregorian ISO dates (`YYYY`, `YYYY-MM`, `YYYY-MM-DD`) alongside existing GEDCOM dates. Month/day values are validated and converted to GEDCOM before event generation and Biography output.
- Added separately selectable standard Gramps filenames such as `de_DE_data_v1_0.csv`, preserving the full collection identifier and persistent-file precedence.
- Retained support for `#` comment lines. Metadata interpretation, `Today` and event IDs are not part of this change.
- Fixed category headings absorbing or repeating the age in Potts Modern.
- Included the live 1.1.1 Biography provider while preserving main's German collection, calendar annotations, data corrections, settings breadcrumbs and compact History button.

### Validation

The date, standard-filename and age-heading changes were tested on the maintainer's live site using the 1.1.2 release candidates. The reconciled source was checked separately in PHP against the combined bundled datasets, with DOM checks for the heading repair. Existing main-branch data files and settings view are preserved byte-for-byte.

### Upgrade

Back up the current module folder, extract the installation ZIP and replace matching files in `modules_v4/potts_historical_facts`. Clear the webtrees cache. Existing settings and persistent data-folder CSV files are retained. Remove any synthetic test CSV files used during release-candidate testing.

The calendar-transition audit (#7) and Italian localisation (#9) remain open. This release does not claim complete support for every feature of the evolving shared Gramps format.
