<?php

namespace alanjancic\sprout\factories;

use Craft;
use craft\base\Element;
use Faker\Factory as FakerFactory;
use Faker\Generator;
use RuntimeException;

/**
 * Extend this in config/factories/ to describe how to build one element.
 * Mirrors Laravel's factory API deliberately: ->count()->create() reads the same.
 */
abstract class Factory
{
    protected Generator $faker;
    protected int $countValue = 1;
    protected array $overrides = [];

    public function __construct()
    {
        $this->faker = FakerFactory::create();
    }

    /** The element class this factory builds, e.g. \craft\elements\Entry::class */
    abstract public function elementClass(): string;

    /** Field/attribute values for one element. Called fresh for every element in the batch. */
    abstract public function definition(): array;

    public function count(int $n): static
    {
        $this->countValue = $n;
        return $this;
    }

    /** Attribute overrides applied on top of definition() for every element in this batch. */
    public function state(array $overrides): static
    {
        $this->overrides = array_merge($this->overrides, $overrides);
        return $this;
    }

    /**
     * Builds and saves the batch. All-or-nothing: if any element in the batch fails to
     * save, everything already created in this same call is deleted again before the
     * exception propagates, so a failed run never leaves orphaned, untracked content
     * behind that `seed/clean` wouldn't know about.
     * @return Element[]
     */
    public function create(): array
    {
        $elementsService = Craft::$app->getElements();
        $created = [];
        for ($i = 0; $i < $this->countValue; $i++) {
            $element = $this->make();
            if (!$elementsService->saveElement($element)) {
                foreach ($created as $partial) {
                    $elementsService->deleteElement($partial, true);
                }
                throw new RuntimeException(
                    'Seeder failed to save a ' . $this->elementClass()
                    . ' (element ' . ($i + 1) . ' of ' . $this->countValue . '): '
                    . implode(', ', $element->getFirstErrors())
                );
            }
            $created[] = $element;
        }
        return $created;
    }

    /** Builds one element without saving it, in case a seeder wants to batch/customize before save. */
    public function make(): Element
    {
        $class = $this->elementClass();
        /** @var Element $element */
        $element = new $class();
        foreach (array_merge($this->definition(), $this->overrides) as $key => $value) {
            $element->{$key} = $value;
        }

        // Entries/categories/etc. don't get a slug generated automatically outside the
        // CP form flow. Every factory would otherwise fail on "Slug cannot be blank."
        // the first time someone doesn't think to set one themselves.
        if (
            property_exists($element, 'slug')
            && empty($element->slug)
            && property_exists($element, 'title')
            && !empty($element->title)
        ) {
            $element->slug = \craft\helpers\ElementHelper::generateSlug($element->title);
        }

        return $element;
    }

    // --- Convenience resolvers, since these lookups are the same in every project's factories ---

    protected function sectionId(string $handle): int
    {
        $section = Craft::$app->getEntries()->getSectionByHandle($handle);
        if (!$section) {
            throw new RuntimeException("No section with handle \"{$handle}\" exists.");
        }
        return $section->id;
    }

    protected function entryTypeId(string $sectionHandle, ?string $typeHandle = null): int
    {
        $section = Craft::$app->getEntries()->getSectionByHandle($sectionHandle);
        if (!$section) {
            throw new RuntimeException("No section with handle \"{$sectionHandle}\" exists.");
        }
        $entryTypes = $section->getEntryTypes();
        $type = $typeHandle
            ? current(array_filter($entryTypes, fn ($t) => $t->handle === $typeHandle))
            : ($entryTypes[0] ?? null);
        if (!$type) {
            throw new RuntimeException("No matching entry type found for section \"{$sectionHandle}\".");
        }
        return $type->id;
    }

    /** A random existing asset's ID from the given volume, or null if the volume is empty. */
    protected function randomAssetId(string $volumeHandle): ?int
    {
        $asset = \craft\elements\Asset::find()->volume($volumeHandle)->orderBy('RAND()')->one();
        return $asset?->id;
    }

    /** A random existing entry's ID from the given section, or null if it's empty. */
    protected function randomEntryId(string $sectionHandle): ?int
    {
        $entry = \craft\elements\Entry::find()->section($sectionHandle)->orderBy('RAND()')->one();
        return $entry?->id;
    }
}
