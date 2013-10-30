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

### IniConfigFile::updateFile

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

```php
<?php
use Composer\Script\Event;
use WMC\Composer\Utils\ConfigFile\IniConfigFile;

class ScriptHandler
{
    public static function myHandler(Event $event)
    {
        $configFile = new IniConfigFile($event->getIO());
        $configFile->updateFile('database.ini', 'database.dist.ini');
    }
}
?>
```

## Author

 * [Sébastien Lavoie](http://www.wemakecustom.com)

## Notes

Should work with PHP 5.3 but tests require 5.4 +