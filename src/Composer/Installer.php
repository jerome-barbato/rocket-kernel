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

    private $files, $symlinks, $event, $io, $builder_path;

    public function __construct(Event $event)
    {
        $this->files    = new FileManager( $event );
        $this->event    = $event;
        $this->io       = $event->getIO();

        $this->builder_path = getcwd() . DIRECTORY_SEPARATOR . "vendor/metabolism/rocket-builder";

    }

    /**
     * Composer initializer
     *
     */
    public static function init(Event $event)
    {

        passthru( "git lfs install" );

        self::checkComposerVersion($event);
    }

    /**
     * Composer initializer
     */
    public static function build(Event $event)
    {
        $installer = Installer::getInstance( $event );

        $args = $event->getArguments();

        if ( is_dir( $installer->builder_path ) ) {
            chdir( $installer->builder_path );
            $options = count( $args ) ? $args[0] : '';

            if ( !is_dir( 'node_modules' ) ) {
                $installer->installNodeModules();
            }

            passthru( "gulp " . $options . " --color=always" );
        }
    }

    /**
     * Composer create
     */
    public static function create(Event $event)
    {
        $installer = Installer::getInstance( $event );

        $args = $event->getArguments();


        if ( is_dir( $installer->builder_path ) ) {
            chdir( $installer->builder_path );

            if ( !is_dir( 'node_modules' ) ) {
                $installer->installNodeModules();
            }

            if ( count( $args ) > 1 ) {

                $type = $args[0];
                array_shift( $args );

                foreach ( $args as $arg ) {
                    passthru( "gulp create --" . $type . " " . $arg . "  --color=always" );
                }
            }
        }
    }

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

    /**
     * Start Rocket-Builder NPM dependencies installation
     */
    public function installNodeModules()
    {
        if ( is_dir( $this->builder_path ) ) {
            $this->io->write( '  Installing node modules...' );
            chdir( $this->builder_path );

            if ( is_dir( 'node_modules' ) ) {
                passthru( "yarn upgrade --production" );
            }
            else {
                passthru( "yarn install --production" );
            }
        }
    }


    /**
     * Checks if the installed version of Composer is compatible.
     *     *
     * @see https://github.com/composer/composer/pull/5035
     */
    public static function checkComposerVersion(Event $event)
    {

        $composer = $event->getComposer();
        $io = $event->getIO();
        $version = $composer::VERSION;
        // The dev-channel of composer uses the git revision as version number,
        // try to the branch alias instead.
        if (preg_match('/^[0-9a-f]{40}$/i', $version)) {
            $version = $composer::BRANCH_ALIAS_VERSION;
        }
        // If Composer is installed through git we have no easy way to determine if
        // it is new enough, just display a warning.
        if ($version === '@package_version@' || $version === '@package_branch_alias_version@') {
            $io->writeError('<warning>You are running a development version of Composer. If you experience problems, please update Composer to the latest stable version.</warning>');
        }
        elseif (Comparator::lessThan($version, '1.3.0')) {
            $io->writeError('<error>Rocket requires Composer version 1.3.0 or higher. Please update your Composer before continuing</error>.');
            exit(1);
        }
    }
}
