<?php

namespace WMC\Composer\Utils\Composer;

use Composer\Composer;

class PackageLocator
{
    /**
     * Return the full install path of a package
     *
     * @param  Composer $composer
     * @param  string $packageName
     * @return string install path
     */
    public static function getPackagePath(Composer $composer, $packageName)
    {
        $repo        = $composer->getRepositoryManager()->getLocalRepository();
        $install_mgr = $composer->getInstallationManager();
        $packages    = $repo->findPackages($packageName, null);

        foreach ($packages as $package) {
            if ($install_mgr->getInstaller($package->getType())->isInstalled($repo, $package)) {
                return $install_mgr->getInstallPath($package);
            }
        }
    }

    /**
     * Return the base project path
     *
     * @param  Composer $composer
     * @return string install path
     */
    public static function getBasePath(Composer $composer)
    {
        return getcwd(); // At the moment, I do not know any better way.
    }
}
