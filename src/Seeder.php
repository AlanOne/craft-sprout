<?php

namespace alanjancic\sprout;

/**
 * Extend this class in config/seeders/ to define a named, runnable seeder.
 * A seeder's job is to call one or more factories and return what got created.
 */
abstract class Seeder
{
    /**
     * @param int|null $countOverride overrides whatever count the seeder normally uses,
     *                                 passed through from `--count` on the CLI.
     * @return \craft\base\Element[] every element that was created, for tracking/cleanup.
     */
    abstract public function run(?int $countOverride = null): array;
}
