<?php
/**
 * User: Paul Coudeville <paul@metabolism.fr>
 */

namespace Rocket\System;

use Composer\Script\Event;

/**
 * Class Installer
 * @package Rocket\Tools
 */
class Installer
{
    private static $instance;
    private $files, $symlinks, $event, $io;


    /**
     * Composer initializer
     */
    public static function init(Event $event)
    {
        $installer = Installer::getInstance($event);

        $installer->createFolders();

        $installer->clean();

        $dev_dependencies = $event->getComposer()->getPackage()->getDevRequires();
        if (array_key_exists("metabolism/rocket-builder", $dev_dependencies) && is_dir("vendor/metabolism/rocket-builder") ) {
            $installer->installAssets();
        }
    }

    /**
     * Composer initializer
     */
    public static function build(Event $event)
    {
        $builder_path   = getcwd() . DIRECTORY_SEPARATOR . "app/resources/builder";
        $args = $event->getArguments();

        if (is_dir($builder_path))
        {
            chdir($builder_path);
            $options = count($args) ? $args[0]:'';

            if (!is_dir('node_modules'))
                passthru("yarn install --production");

                passthru("gulp ".$options." --color=always");
            }
    }

    /**
     * Composer create
     */
    public static function create(Event $event)
    {
        $builder_path   = getcwd() . DIRECTORY_SEPARATOR . "app/resources/builder";
        $args = $event->getArguments();

        if (is_dir($builder_path))
        {
            chdir($builder_path);

            if (!is_dir('node_modules'))
                passthru("yarn install --production");

            if( count($args) > 1){

                $type = $args[0];
                array_shift($args);

                foreach ($args as $arg)
                    passthru("gulp create --".$type." ".$arg."  --color=always");
            }
        }
    }

    /**
     * Singleton instance retriever
     * @return Installer
     */
    public static function getInstance(Event $event)
    {
        if (is_null(self::$instance)) {
            self::$instance = new Installer($event);
        }
        return self::$instance;
    }

    public function __construct(Event $event)
    {
        $this->files = new Files();
        $this->symlinks = new Symlink();
        $this->event = $event;
        $this->io = $event->getIO();
    }

    /**
     * @return Files
     */
    public function getFiles()
    {

        return $this->files;
    }

    /**
     * @return Symlink
     */
    public function getSymlinks()
    {

        return $this->symlinks;
    }

    /**
     * Creating importants files for next steps
     */
    public function createFolders()
    {
        // Creating missing folders
        $this->getFiles()->createFolder($this->event);

        // Symlinking
        $this->getSymlinks()->create($this->event);

        // Copying important files
        $this->getFiles()->copy($this->event);
    }

    /**
     * Removing files
     */
    public function clean()
    {
        // Removing temporary files
        $this->getFiles()->remove($this->event);
    }

    /**
     * Start Rocket-Builder NPM dependencies installation
     */
    public function installAssets()
    {
        $builder_path   = getcwd() . DIRECTORY_SEPARATOR . "app/resources/builder";

        if (is_dir($builder_path))
        {
            $this->io->write('  Installing Builder...');
            $this->io->write(sprintf('  Moving to <comment>%s</comment>.', $builder_path));
            chdir($builder_path);

            if (is_dir('node_modules'))
            {
                passthru("yarn upgrade --production");
            }
            else
            {
                passthru("yarn install --production");
            }

            passthru("gulp -p --color=always");
        }
    }
}
