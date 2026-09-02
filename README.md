# Seeder

Laravel-style factories and seeders for Craft CMS — generate and tear down realistic demo
content from the command line, with a backup-safe, all-or-nothing create step.

If you came from Laravel, this is deliberately familiar:

```php
// config/factories/BlogPostFactory.php
class BlogPostFactory extends Factory
{
    public function elementClass(): string { return Entry::class; }

    public function definition(): array
    {
        return [
            'sectionId' => $this->sectionId('blog'),
            'typeId' => $this->entryTypeId('blog'),
            'title' => $this->faker->sentence(6),
            'body' => $this->faker->paragraphs(3, true),
        ];
    }
}
```

```bash
php craft seeder/seed/make-factory BlogPost   # scaffold config/factories/BlogPostFactory.php
php craft seeder/seed/make-seeder BlogPost    # scaffold config/seeders/BlogPostSeeder.php
php craft seeder/seed/run --count=20          # run every seeder (or --only=BlogPostSeeder)
php craft seeder/seed/list                    # see every registered seeder + how much it's seeded
php craft seeder/seed/clean                   # delete everything seed/run has ever created
```

## Why not just write a script?

Because tearing it back down is the part everyone skips. Every element `seed/run` creates
is logged. `seed/clean` deletes exactly that — nothing more, nothing less — and asks for
confirmation first. A batch that fails partway through rolls itself back automatically, so a
broken factory never leaves orphaned content that `clean` doesn't know about.

## Installation

```bash
composer require alanjancic/craft-seeder
php craft plugin/install seeder
```

## Requirements

Craft CMS 5.0 or later.

## Writing a factory

Extend `alanjancic\seeder\factories\Factory` and implement `elementClass()` and
`definition()`. A few resolver helpers are available for the lookups every factory needs:

- `$this->sectionId('handle')`
- `$this->entryTypeId('sectionHandle', ?'entryTypeHandle')`
- `$this->randomAssetId('volumeHandle')` — links to an existing asset, or null if empty
- `$this->randomEntryId('sectionHandle')` — links to an existing entry, or null if empty
- `$this->faker` — a full [FakerPHP](https://fakerphp.org) generator instance

Chain `->count(20)` and `->state([...])` (attribute overrides) before `->create()`.

## Writing a seeder

Extend `alanjancic\seeder\Seeder` and implement `run(?int $countOverride): array`, returning
whatever the factory created — that return value is what gets tracked for `seed/clean`.

## License

Commercial. See the Craft Plugin Store listing for pricing and licensing terms.
