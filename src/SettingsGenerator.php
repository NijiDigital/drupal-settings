<?php

namespace NijiDigital\Settings;

use Composer\Composer;
use Composer\IO\IOInterface;
use Drupal\Component\Utility\Crypt;
use Composer\Script\Event;
use Symfony\Component\Yaml\Yaml;
use Twig_Environment;
use Twig_Loader_Filesystem;

/**
 * Class SettingsGenerator
 */
class SettingsGenerator {

    /**
     * @var \Composer\Composer
     */
    protected $composer;

    /**
     * @var \Composer\IO\IOInterface
     */
    protected $io;

    /**
     * @var \Composer\Script\Event
     */
    protected $event;

    /**
     * The composer extra definitions.
     *
     * @var array|null
     */
    protected $extra = null;

    /**
     * @var \Twig_Environment
     */
    protected $twigEnvironment;

    protected $basePath;

    /**
     * Handler constructor.
     *
     * @param \Composer\Composer $composer
     * @param \Composer\IO\IOInterface $io
     */
    public function __construct(Composer $composer, IOInterface $io, Event $event) {
        $this->composer = $composer;
        $this->io = $io;
        $this->event = $event;
        $this->setExtra($composer);
        $this->twigEnvironment = new Twig_Environment(new Twig_Loader_Filesystem($this->getTemplatePath()));
        $this->basePath = getcwd() . '/';
    }

    /**
     * Prepare the settings.local.php file
     */
    public function generate() {
        $this->event->getIO()->write("<info>Generate settings file:</info>");

        $parameters = $this->getParameters();
        if ($parameters) {
            $new_settings = $this->twigEnvironment->render($this->getTemplateFilename(), $this->getReplacements($parameters));
            $target_settings_file = $this->getDestinationPath() . '/' . $this->getDestinationFile();

            // Ensure folder and existing file is writable.
            chmod($this->getDestinationPath(), 0755);
            if (file_exists($target_settings_file)) {
                chmod($target_settings_file, 0644);
            }

            file_put_contents($target_settings_file, $new_settings);
        }
        else {
            $this->event->getIO()->write("<error>Unable to find any parameters files</error>");
        }
    }

    /**
     * Get replacement parameters.
     *
     * @param $parameters
     *   The parameters from yaml file.
     *
     * @return array
     */
    protected function getReplacements($parameters) {
        $replacement = [];
        foreach ($parameters as $setting_key => $setting_value) {
            $replacement[$setting_key] = $setting_value;

            // Special case for array parameter.
            if (is_array($setting_value)) {
                $replacement[$setting_key] = "[\n";
                foreach ($setting_value as $value) {
                    $replacement[$setting_key] .= "  '" . $value . "',\n";
                }
                $replacement[$setting_key] .= "]";
            }
        }

        if (!isset($replacement['hash_salt'])) {
            $replacement['hash_salt'] = Crypt::randomBytesBase64(55);
        }

        return $replacement;
    }

    /**
     * Get composer "drupal-settings" extra definitions.
     *
     *   "drupal-settings": {
     *     "parameters_file": "parameters.yml",
     *     "template-directory": "templates",
     *     "template-file": "settings.local.php",
     *     "destination-directory": "web/sites/default",
     *     "destination-file": "settings.local.php"
     *   }
     *
     * @return array|null
     */
    protected function setExtra(Composer $composer) {
        $extra = $composer->getPackage()->getExtra();
        if (isset($extra['drupal-settings'])) {
            $this->extra = $extra['drupal-settings'];
        }
    }

    /**
     * Get the template path.
     *
     * @return string
     */
    protected function getTemplatePath() {
        if (isset($this->extra['template-directory'])) {
            return $this->basePath . $this->extra['template-directory'];
        }
        return $this->composer->getConfig()->get('vendor-dir') . '/niji-digital/drupal-settings/templates/';
    }

    /**
     * Get the template file name.
     *
     * @return string
     */
    protected function getTemplateFilename() {
        if (isset($this->extra['template-file'])) {
            return $this->extra['template-file'];
        }
        return 'settings.local.php.twig';
    }

    /**
     * Get the destination path
     *
     * @return string
     */
    protected function getDestinationPath() {
        $destination_path = 'web/sites/default/';
        if (isset($this->extra['destination-directory'])) {
            $destination_path = $this->extra['destination-directory'];
        }
        return $this->basePath . $destination_path;
    }

    /**
     * Get the destination file
     *
     * @return string
     */
    protected function getDestinationFile() {
        if (isset($this->extra['destination-file'])) {
            return $this->extra['destination-file'];
        }
        return 'settings.local.php';
    }

    /**
     * Get parameters.
     *
     * @return mixed
     *   The YAML converted to a PHP value
     */
    protected function getParameters() {
        $parameters = $this->getParameterFileContent();
        if ($parameters) {
            return Yaml::parse($parameters);
        }
        return null;
    }

    /**
     * Get parameters file list in order of priority.
     *
     * 1. The parameter file defined in composer extra
     *   @see \NijiDigital\Settings\SettingsGenerator::setExtra()
     *
     * 2. drupal-settings/parameters.yml
     * 3. drupal-settings/parameters.dist.yml
     *
     * @return array
     */
    protected function getParameterFiles() {
        // By default.
        $parameter_files = [
            'drupal-settings/parameters.yml',
            'drupal-settings/parameters.dist.yml'
        ];

        if ($this->extra && isset($this->extra['parameters-file'])) {
            array_unshift($parameter_files, $this->extra['parameters-file']);
        }

        return $parameter_files;
    }

    /**
     * Get the parameter file content.
     *
     * @return bool|null|string
     */
    protected function getParameterFileContent() {
        $files = $this->getParameterFiles();
        foreach ($files as $parameter_file) {
            if (file_exists($this->basePath . $parameter_file)) {
                $this->event->getIO()->write("Create the settings file from the $parameter_file file");
                return file_get_contents($this->basePath . $parameter_file);
            }
            else {
                $this->event->getIO()->write("Parameter file $parameter_file doesn't exists" . (($next = next($files)) ? ', trying with ' . $next : ''));
            }
        }
        return null;
    }

}
