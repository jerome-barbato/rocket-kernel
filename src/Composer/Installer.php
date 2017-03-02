<?php
/**
 * User: Paul Coudeville <paul@metabolism.fr>
 */

namespace Rocket\Composer;


use Composer\Package\Package;
use Composer\Script\Event;
use Composer\Installer\PackageEvent;
use Composer\Semver\Comparator;
use Rocket\Application\SingletonTrait;
use Rocket\System\FileManager;

/**
 * Class Installer
 *
 * @package Rocket\System
 */
class Installer {

    use SingletonTrait;

    /**
     * Creating importants files for next steps
     */
    public static function install(PackageEvent $event)
    {
        /** @var Package $installedPackage */
        $installedPackage = $event->getOperation()->getPackage();

        /** @var Package $root_pkg */
        $root_pkg = $event->getComposer()->getPackage();
        $extras = $root_pkg->getExtra();


        if (isset($extras["file-management"]))
        {
            foreach ($extras['file-management'] as $action => $pkg_names) {

                if (array_key_exists($installedPackage->getName(), $pkg_names)) {

                    /** @var FileManager $fm */
                    $fm = FileManager::getInstance($event);

                    if (method_exists($fm, $action)) {

                        try {

                            $fm->$action($pkg_names[$installedPackage->getName()], $installedPackage, $event->getIO());
                        } catch (\Exception $e) {

                            $event->getIO()->writeError("<error>Error: " . $action . " action on " . $installedPackage->getName() . " : \n" . $e->getMessage() . "</error>");                        }
                    } else {

                        $event->getIO()->writeError("<warning> Skipping extra folder action : " . $action . ", method does not exist.</warning>");
                    }
                }

            }
        }
    }
}
