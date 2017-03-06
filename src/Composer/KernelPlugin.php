<?php
/**
 * User: Paul Coudeville <paul@metabolism.fr>
 */

namespace Rocket\Composer;

use Composer\Composer;
use Composer\Installer\PackageEvent;
use Composer\IO\IOInterface;
use Composer\Package\Package;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;
use Composer\Script\ScriptEvents;
use Rocket\System\FileManager;

/**
 * Class KernelPlugin
 *
 * @package Rocket\Composer
 */
class KernelPlugin implements PluginInterface, Capable, EventSubscriberInterface
{
    protected $composer;
    protected $io;

    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io       = $io;

    }


    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ScriptEvents::POST_PACKAGE_INSTALL => [
                [
                    'onPostPackageInstall',
                    0
                ]
            ]
        ];
    }

    public function getCapabilities()
    {
        return [
            'Composer\Plugin\Capability\CommandProvider' => 'Rocket\Composer\Command\KernelCommandProvider'
        ];
    }



    /**
     * Creating importants files for next steps
     */
    public function onPostPackageInstall(PackageEvent $event)
    {
        /** @var Package $installedPackage */
        $installedPackage = $event->getOperation()->getPackage();

        /** @var Package $root_pkg */
        $root_pkg = $event->getComposer()->getPackage();
        $extras   = $installedPackage->getExtra();


        if ( isset( $extras["file-management"] ) )
        {
            foreach ( $extras['file-management'] as $action => $pkg_names )
            {

                if ( array_key_exists( $installedPackage->getName(), $pkg_names ) )
                {

                    /** @var FileManager $fm */
                    $fm = FileManager::getInstance( $this->io );

                    if ( method_exists( $fm, $action ) )
                    {

                        try
                        {

                            $fm->$action( $pkg_names[$installedPackage->getName()], $installedPackage, $event->getIO() );
                        } catch ( \Exception $e )
                        {

                            $this->io->writeError( "<error>Error: " . $action . " action on " . $installedPackage->getName() . " : \n" . $e->getMessage() . "</error>" );
                        }
                    }
                    else
                    {

                        $this->io->writeError( "<warning> Skipping extra folder action : " . $action . ", method does not exist.</warning>" );
                    }
                }

            }
        }
    }
}
