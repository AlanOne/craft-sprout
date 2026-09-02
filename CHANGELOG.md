# Changelog

## 1.0.0
- Initial release.
- `Factory` base class with Faker integration, `count()`/`state()`/`create()`, and
  `sectionId`/`entryTypeId`/`randomAssetId`/`randomEntryId` resolver helpers.
- `Seeder` base class for grouping factory calls into named, runnable units.
- `seeder/seed/run`, `seeder/seed/clean`, `seeder/seed/list` console commands.
- `seeder/seed/make-factory` and `seeder/seed/make-seeder` scaffolding commands.
- All-or-nothing batch creation: a failed factory batch rolls back everything it already
  created in the same call, so `seed/clean` can never be left with untracked orphans.
- Automatic slug generation for titled elements created outside the normal CP form flow.
