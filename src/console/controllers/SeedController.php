<?php

namespace alanjancic\sprout\console\controllers;

use Craft;
use craft\console\Controller;
use craft\db\Query;
use craft\helpers\Console;
use craft\helpers\Db;
use craft\helpers\FileHelper;
use craft\helpers\StringHelper;
use DateTime;
use yii\console\ExitCode;

class SeedController extends Controller
{
    public ?string $only = null;
    public ?int $count = null;

    public function options($actionID): array
    {
        $options = parent::options($actionID);
        if (in_array($actionID, ['run', 'clean'], true)) {
            $options[] = 'only';
        }
        if ($actionID === 'run') {
            $options[] = 'count';
        }
        return $options;
    }

    public function optionAliases(): array
    {
        return ['o' => 'only', 'c' => 'count'];
    }

    /** Runs every registered seeder in config/seeders/, or just --only=<name>. */
    public function actionRun(): int
    {
        $seeders = $this->registeredSeeders();

        if (empty($seeders)) {
            $this->stderr("No seeders found under config/seeders/. Run `craft sprout/seed/make-seeder MyThing` to create one.\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        if ($this->only && !isset($seeders[$this->only])) {
            $this->stderr("No seeder named \"{$this->only}\". Known seeders: " . implode(', ', array_keys($seeders)) . "\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        foreach ($seeders as $name => $class) {
            if ($this->only && $this->only !== $name) {
                continue;
            }

            $this->stdout("Seeding {$name}... ");
            $created = (new $class())->run($this->count);
            $this->logSeeded($created, $name);
            $this->stdout('done (' . count($created) . " elements).\n", Console::FG_GREEN);
        }

        return ExitCode::OK;
    }

    /** Deletes everything seed/run has created, or just --only=<name>. Asks for confirmation first. */
    public function actionClean(): int
    {
        $query = (new Query())->from('{{%sprout_log}}');
        if ($this->only) {
            $query->andWhere(['seederName' => $this->only]);
        }
        $rows = $query->all();

        if (empty($rows)) {
            $this->stdout("Nothing to clean.\n", Console::FG_YELLOW);
            return ExitCode::OK;
        }

        if (!$this->confirm('This will permanently delete ' . count($rows) . ' seeded element(s). Continue?')) {
            return ExitCode::OK;
        }

        $elements = Craft::$app->getElements();
        $deleted = 0;
        foreach ($rows as $row) {
            $element = $elements->getElementById($row['elementId']);
            if ($element && $elements->deleteElement($element, true)) {
                $deleted++;
            }
        }

        Craft::$app->getDb()->createCommand()
            ->delete('{{%sprout_log}}', $this->only ? ['seederName' => $this->only] : '')
            ->execute();

        $this->stdout("Deleted {$deleted} seeded element(s).\n", Console::FG_GREEN);
        return ExitCode::OK;
    }

    /** Lists every seeder Craft can currently find, and how many elements each has seeded so far. */
    public function actionList(): int
    {
        $seeders = $this->registeredSeeders();
        if (empty($seeders)) {
            $this->stdout("No seeders found under config/seeders/.\n", Console::FG_YELLOW);
            return ExitCode::OK;
        }

        foreach ($seeders as $name => $class) {
            $count = (new Query())->from('{{%sprout_log}}')->where(['seederName' => $name])->count();
            $this->stdout(sprintf("%-24s %s  (%d seeded)\n", $name, $class, $count));
        }
        return ExitCode::OK;
    }

    /** `craft sprout/seed/make-factory Foo` — scaffolds config/factories/FooFactory.php. */
    public function actionMakeFactory(string $name): int
    {
        $className = str_ends_with($name, 'Factory') ? $name : "{$name}Factory";
        $dir = Craft::getAlias('@config/factories');
        FileHelper::createDirectory($dir);
        $path = "{$dir}/{$className}.php";

        if (file_exists($path)) {
            $this->stderr("{$path} already exists.\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        file_put_contents($path, <<<PHP
<?php

namespace config\\factories;

use alanjancic\\sprout\\factories\\Factory;
use craft\\elements\\Entry;

class {$className} extends Factory
{
    public function elementClass(): string
    {
        return Entry::class;
    }

    public function definition(): array
    {
        return [
            // 'sectionId' => \$this->sectionId('yourSectionHandle'),
            // 'typeId' => \$this->entryTypeId('yourSectionHandle'),
            'title' => \$this->faker->sentence(6),
        ];
    }
}

PHP);

        $this->stdout("Created {$path}\n", Console::FG_GREEN);
        return ExitCode::OK;
    }

    /** `craft sprout/seed/make-seeder Foo` — scaffolds config/seeders/FooSeeder.php, wired to FooFactory. */
    public function actionMakeSeeder(string $name): int
    {
        $base = str_ends_with($name, 'Seeder') ? substr($name, 0, -6) : $name;
        $className = "{$base}Seeder";
        $factoryClassName = "{$base}Factory";
        $dir = Craft::getAlias('@config/seeders');
        FileHelper::createDirectory($dir);
        $path = "{$dir}/{$className}.php";

        if (file_exists($path)) {
            $this->stderr("{$path} already exists.\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        file_put_contents($path, <<<PHP
<?php

namespace config\\seeders;

use alanjancic\\sprout\\Seeder;
use config\\factories\\{$factoryClassName};

class {$className} extends Seeder
{
    public function run(?int \$countOverride = null): array
    {
        return (new {$factoryClassName}())->count(\$countOverride ?? 10)->create();
    }
}

PHP);

        $this->stdout("Created {$path}\n", Console::FG_GREEN);
        return ExitCode::OK;
    }

    private function registeredSeeders(): array
    {
        $path = Craft::getAlias('@config/seeders');
        $seeders = [];
        if (is_dir($path)) {
            foreach (glob($path . '/*.php') as $file) {
                $name = basename($file, '.php');
                $class = 'config\\seeders\\' . $name;
                if (class_exists($class)) {
                    $seeders[$name] = $class;
                }
            }
        }
        ksort($seeders);
        return $seeders;
    }

    private function logSeeded(array $elements, string $seederName): void
    {
        if (empty($elements)) {
            return;
        }
        $db = Craft::$app->getDb();
        $now = Db::prepareDateForDb(new DateTime());
        $rows = array_map(fn ($element) => [
            $element->id,
            get_class($element),
            $seederName,
            $now,
            $now,
            StringHelper::UUID(),
        ], $elements);

        $db->createCommand()->batchInsert(
            '{{%sprout_log}}',
            ['elementId', 'elementType', 'seederName', 'dateCreated', 'dateUpdated', 'uid'],
            $rows
        )->execute();
    }
}
