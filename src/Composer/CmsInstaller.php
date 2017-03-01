<?php
/**
 * User: Paul Coudeville <paul@metabolism.fr>
 */

namespace Rocket\Composer;


use Composer\Package\PackageInterface;
use Composer\Installer\LibraryInstaller;

class CmsInstaller extends LibraryInstaller
{

    private static $supported_types = array("wordpress-core", "rocket-cms", "drupal-core");
    private static $_installedPaths = array();
    /**
     * {@inheritDoc}
     */
    public function getInstallPath(PackageInterface $package)
    {
        $installationDir = false;
        $prettyName      = $package->getPrettyName();
        if ( $this->composer->getPackage() ) {
            $topExtra = $this->composer->getPackage()->getExtra();
            if ( ! empty( $topExtra['cms-install-dir'] ) ) {
                $installationDir = $topExtra['cms-install-dir'];
                if ( is_array( $installationDir ) ) {
                    $installationDir = empty( $installationDir[$prettyName] ) ? false : $installationDir[$prettyName];
                }
            }
        }
        $extra = $package->getExtra();
        if ( ! $installationDir && ! empty( $extra['cms-install-dir'] ) ) {
            $installationDir = $extra['cms-install-dir'];
        }
        if ( ! $installationDir ) {
            $installationDir = 'web/edition';
        }
        if (
            ! empty( self::$_installedPaths[$installationDir] ) &&
            $prettyName !== self::$_installedPaths[$installationDir]
        ) {
            throw new \InvalidArgumentException( 'Two packages cannot share the same directory!' );
        }
        self::$_installedPaths[$installationDir] = $prettyName;
        return $installationDir;
    }

    /**
     * {@inheritDoc}
     */
    public function supports($packageType)
    {
        return in_array($packageType, CmsInstaller::$supported_types);
    }
}
