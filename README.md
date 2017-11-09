# Drupal settings generator

Composer plugin for generate the Drupal `settings.local.php` (or other) file from a yaml parameter file.

## Usage

* Run `composer require niji-digital/niji-digital/drupal-settings`
* Add new script to the scripts section of your composer.json:
```json
{
  "scripts": {
    "prepare-settings": "NijiDigital\\Settings\\Plugin::generate"
  }
}
```
* Create the YAML parameters file (by default in `COMPOSER_ROOT/drupal-settings` directory, see [Parameters](#parameters) section)
* *Optional: Create the settings file template (see [Template](#template) section)*
* *Optional: Change the settings file destination (see [Destination](#destination) section)*

* **Run `composer prepare-settings`**

## From "Template" using "Parameters" to "Destination"

**All path parameters must be relative to the composer root directory**

### Template

By default the `settings.local.php.twig` template file present in this repository is used.
```twig
{% autoescape false %}
<?php

//$settings['container_yamls'][] = DRUPAL_ROOT . '/sites/development.services.yml';
//$settings['cache']['default'] = 'cache.backend.null';
$settings['extension_discovery_scan_tests'] = TRUE;
$settings['file_chmod_directory'] = 0775;
$settings['file_chmod_file'] = 0664;
$settings['hash_salt'] = '{{ hash_salt }}';

$databases['default']['default'] = array(
    'driver' => 'mysql',
    'database' => '{{ db_name }}',
    'username' => '{{ db_user }}',
    'password' => '{{ db_pass }}',
    'host' => '{{ db_host }}',
    'port' => 3306,
    'prefix' => '',
    'collation' => 'utf8mb4_general_ci',
    'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
);

$config['system.logging']['error_level'] = '{{ error_level }}';
$config['system.performance']['css']['preprocess'] = {{ css_preprocess }};
$config['system.performance']['js']['preprocess'] = {{ js_preprocess }};
$config['system.performance']['cache.page.max_age'] = {{ cache_maxage }};

$settings['trusted_host_patterns'] = {{ trusted_host_patterns }};

$config_directories = array(
    CONFIG_SYNC_DIRECTORY => getcwd() . '/../config/'
);
{% endautoescape %}

```

You can make your own template and add his definition in the composer extra data definition:

```json
{
    "extra": {
        "settings-generator": {
            "template-directory": "path/to/templates",
            "template-file": "settings.local.php.twig"
        }
    }
}
```

### Parameters

Example:
```yaml
cache_maxage: '300'
css_preprocess: 'TRUE'
db_host: 'mariadb'
db_name: 'drupal'
db_pass: 'drupal'
db_user: 'drupal'
error_level: 'verbose'
js_preprocess: 'TRUE'
trusted_host_patterns:
  - '^drupal\.localhost$'
```

This plugin tried to load parameter file (in order):
1. The file defined in composer extra data definitions:
```json
{
    "extra": {
        "settings-generator": {
            "parameters-file": "../path/to/parameters.yml"
        }
    }
}
```
2. `settings/parameters.yml`
3. `settings/parameters.dist.yml`

### Destination

By default the `settings.local.php` file is created in the `web/sites/default` directory.

The destination can be overwritten by a setting in composer extra data definition.

```json
{
    "extra": {
        "settings-generator": {
            "destination-directory": "web/sites/default",
            "destination-file": "settings.local.php"
        }
    }
}
``` 
