<?php

namespace NijiDigital\Settings;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;

class Plugin implements PluginInterface {

    /**
     * {@inheritdoc}
     */
    public function activate(Composer $composer, IOInterface $io) {}

    /**
     * Script callback for putting in composer scripts to generate the
     * settings files.
     *
     * @param \Composer\Script\Event $event
     */
    public static function generate(Event $event) {
        $settings_generator = new SettingsGenerator($event->getComposer(), $event->getIO(), $event);
        $settings_generator->generate();
    }

}
