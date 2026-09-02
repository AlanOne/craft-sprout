# Changelog

## 1.0.0
- Initial release.
- `Factory` base class with Faker integration, `count()`/`state()`/`create()`, and
  `sectionId`/`entryTypeId`/`randomAssetId`/`randomEntryId` resolver helpers.
- `Seeder` base class for grouping factory calls into named, runnable units.
- `sprout/seed/run`, `sprout/seed/clean`, `sprout/seed/list` console commands.
- `sprout/seed/make-factory` and `sprout/seed/make-seeder` scaffolding commands.
- All-or-nothing batch creation: a failed factory batch rolls back everything it already
  created in the same call, so `seed/clean` can never be left with untracked orphans.
- Automatic slug generation for titled elements created outside the normal CP form flow.
