<?php
/**
 * User: Paul Coudeville <paul@metabolism.fr>
 */

namespace Rocket\System;

use Composer\Script\Event;
use Rocket\Application\SingletonTrait;

/**
 * Class Installer
 *
 * @package Rocket\System
 */
class Installer
{

    use SingletonTrait;

    private $files, $symlinks, $event, $io, $builder_path;


    /**
     * Composer initializer
     * @param $event Event
     */
    public static function init(Event $event)
    {
        $installer = Installer::getInstance($event);

        passthru("git lfs install");
        $installer->createFolders();
    }


    /**
     * Composer initializer
     */
    public static function build(Event $event)
    {
        $installer = Installer::getInstance($event);

        $args = $event->getArguments();

        if (is_dir($installer->builder_path))
        {
            chdir($installer->builder_path);
            $options = count($args) ? $args[0]:'';

            if (!is_dir('node_modules'))
                $installer->installNodeModules();

            passthru("gulp ".$options." --color=always");
        }
    }


    /**
     * Composer create
     */
    public static function create(Event $event)
    {
        $installer = Installer::getInstance($event);

        $args = $event->getArguments();

        if (is_dir($installer->builder_path))
        {
            chdir($installer->builder_path);

            if (!is_dir('node_modules'))
                $installer->installNodeModules();

            if( count($args) > 1){

                $type = $args[0];
                array_shift($args);

                foreach ($args as $arg)
                    passthru("gulp create --".$type." ".$arg."  --color=always");
            }
        }
    }


    public function __construct(Event $event)
    {
        $this->files    = new Files($event);
        $this->symlinks = new Symlink();
        $this->event    = $event;
        $this->io       = $event->getIO();

        $this->builder_path = getcwd() . DIRECTORY_SEPARATOR . "vendor/metabolism/rocket-builder";

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
     * Start Rocket-Builder NPM dependencies installation
     */
    public function installNodeModules()
    {
        if (is_dir($this->builder_path))
        {
            $this->io->write('  Installing node modules...');
            chdir($this->builder_path);

            if (is_dir('node_modules'))
                passthru("yarn upgrade --production  --color=always");
            else
                passthru("yarn install --production  --color=always");
        }
    }
}
