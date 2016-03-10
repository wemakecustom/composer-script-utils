Composer Script Utils
=====================

Set of tools for Composer scripts

[![Build Status](https://travis-ci.org/wemakecustom/composer-script-utils.png)](https://travis-ci.org/wemakecustom/composer-script-utils)

## Documentation

### PackageLocator::getPackagePath

Retrieve the full install path of a package

```php
<?php
use Composer\Script\Event;
use WMC\Composer\Utils\Composer\PackageLocator;

class ScriptHandler
{
    public static function myHandler(Event $event)
    {
        $directory = PackageLocator::getPackagePath($event->getComposer(), 'composer/composer');
    }
}
?>
```

### PathUtil::getRelativePath

Short relative path from a file/folder to a file/folder

```php
<?php
use WMC\Composer\Utils\Filesystem\PathUtil;

$relPath = PathUtil::getRelativePath('/tmp/foo/bar', '/tmp/baz'); // ../foo/bar
?>
```

### ConfigFile

Asks interactively for values to fill a configuration file
Values asked are taken from a dist file where its values are used are default values

Ex: ask for database configuration with:
```ini
; database.dist.ini
user=root
pass=root
name=my_database
host=localhost
```

By default, it with read values from environment, using the filename as a prefix. Ex: `DATABASE_USER`
You can override this by specifying a custom environment map using `setEnvMap(array('field' => 'ENV'))`

By default, it will flush outdated parameters (present in the config file, but not in the dist file).
You can override this with `setKeepOutdatedParams`.

#### Supported formats

Currently, json, ini, yml and yaml files are supported.

For command-line input, json_decode will be used, but strings do not need to be quoted.

#### Different formats for dist/target files

It is possible to have a dist file in one format and output a target file in another by concatenating the extensions.
This could be use to generate default values in a PHP script, but still save it as another format.

Ex:

```php
<?php
// dist/foo.ini.php
return array('foo' => 'bar');
?>
```
```ini
; local/foo.ini
foo=bar
```

#### Default Composer script

If you want to use the default configuration without any custom mapping, you can use the included Composer script:

Each file in `path/to/dist/dir` will be compiled to `path/to/target/dir`.

```json
{
    "scripts": {
        "post-install-cmd": [
            "WMC\\Composer\\Utils\\ScriptHandler::updateDirs"
        ],
        "post-update-cmd": [
            "WMC\\Composer\\Utils\\ScriptHandler::updateDirs"
        ]
    },
    "extra": {
        "update-config-dirs": {
            "path/to/dist/dir": "path/to/target/dir"
        }
    }
}
```

#### Custom handling

For more control, use the FileUpdater directly:

```php
<?php
use Composer\Script\Event;
use WMC\Composer\Utils\ScriptHandler as Base;

class ScriptHandler
{
    public static function myHandler(Event $event)
    {
        $configFile = Base::createConfigFileUpdate($event->getIO());
        $configFile->updateFile('database.ini', 'database.ini.dist');
    }
}
?>
```

N.B.: If you want to save your dist files along with your targets (For example,
a `parameters.yml.dist` with the `parameters.yml`), you will need to use Custom
Handling.


## Author

 * [Sébastien Lavoie](http://www.wemakecustom.com)
 * [Mathieu Lemoine](http://www.wemakecustom.com)

## Notes

Tested on PHP 5.3+
