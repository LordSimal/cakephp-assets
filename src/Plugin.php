<?php
declare(strict_types=1);

namespace Assets;

use Cake\Core\BasePlugin;
use Cake\Core\Configure;
use Cake\Core\PluginApplicationInterface;

/**
 * Plugin for Assets
 */
class Plugin extends BasePlugin
{
    /**
     * Load all the plugin configuration and bootstrap logic.
     *
     * The host application is provided as an argument. This allows you to load
     * additional plugin dependencies, or attach events.
     *
     * @param \Cake\Core\PluginApplicationInterface $app The host application
     * @return void
     */
    public function bootstrap(PluginApplicationInterface $app): void
    {
        $this->loadConfig();

        $app->addPlugin('Josegonzalez/Upload');
    }

    /**
     * Loads the config from the user
     *
     * @return void
     */
    private function loadConfig(): void
    {
        Configure::load('Assets.app_assets');

        $configs = [
            'app_assets',
            'app',
            'app_local',
        ];

        foreach ($configs as $config) {
            try {
                Configure::load($config);
            } catch (\Exception $e) {
                continue;
            }
        }
    }
}
