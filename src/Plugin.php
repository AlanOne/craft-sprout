<?php

namespace alanjancic\sprout;

use Craft;
use craft\base\Plugin as BasePlugin;
use craft\console\Application as ConsoleApplication;

class Plugin extends BasePlugin
{
    public static ?Plugin $instance = null;

    public string $schemaVersion = '1.0.0';

    public function init(): void
    {
        parent::init();
        self::$instance = $this;

        if (Craft::$app instanceof ConsoleApplication) {
            $this->controllerNamespace = 'alanjancic\\sprout\\console\\controllers';
        }

        $this->registerConfigAutoloader();
    }

    /**
     * Composer has no PSR-4 mapping for a project's config\factories\ or config\seeders\
     * namespaces — those are directories we scaffold, not packages. Without this, every
     * `use config\factories\FooFactory` in a generated seeder would fatal.
     */
    private function registerConfigAutoloader(): void
    {
        spl_autoload_register(function (string $class): void {
            foreach (['factories', 'seeders'] as $dir) {
                $prefix = "config\\{$dir}\\";
                if (str_starts_with($class, $prefix)) {
                    $relative = substr($class, strlen($prefix));
                    $file = Craft::getAlias("@config/{$dir}") . '/' . $relative . '.php';
                    if (is_file($file)) {
                        require_once $file;
                    }
                    return;
                }
            }
        });
    }
}
