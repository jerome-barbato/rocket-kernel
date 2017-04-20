<?php

namespace Rocket\System;

use Composer\IO\IOInterface;
use Composer\Package\Package;
use Composer\Script\Event;
use Composer\Util\FileSystem;
use Dflydev\DotAccessData\Data;
use Rocket\Application\SingletonTrait;
use Symfony\Component\FileSystem\Exception\IOException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

/**
 * Class SyncManager
 *
 * File Manager
 *
 * @package Rocket\System
 */
class SyncManager
{

    use SingletonTrait;

    private $io, $config;

    /**
     * SyncManager constructor.
     *
     * @param IOInterface $io
     */
    public function __construct(IOInterface $io)
    {
        $this->io    = $io;
    }

    /**
     * Extract ZIP or GZ file
     * @param $archive
     * @param $destination
     * @throws \BadMethodCallException
     */
    public function extract($archive, $destination)
    {

        $filename = getcwd() . DIRECTORY_SEPARATOR . $archive;

        if ( file_exists( $filename ) )
        {
            $file_info = pathinfo( $filename );

            if ( $file_info['extension'] == "zip" )
            {
                passthru( "unzip " . $filename . " -d " . $destination );
            }
            else
            {
                if ( $file_info['extension'] == "gz" )
                {
                    passthru( "tar -zxvf " . $filename . " " . $destination );
                }
                else
                {
                    throw new \BadMethodCallException('Invalid archive format ( zip or tar.gz )');
                }
            }
        }

        throw new \BadMethodCallException($filename . ' does not exists');
    }

    /**
     * @param string $source
     * @param string $archive_name
     * @throws \BadMethodCallException
     */
    public function compress($source, $archive_name="backup")
    {
        $folder   = getcwd() . DIRECTORY_SEPARATOR . $source;

        if ( is_dir( $folder ) )
        {
            passthru( "tar -zchvf " . $archive_name . 'tar.gz ' . $source );
        }
        else
        {
            throw new \BadMethodCallException($folder . ' does not exists');
        }
    }

    /**
     * @param Event $event
     * @param       $id
     * @return array
     */
    protected function get(Event $event, $id)
    {
        $options = $event->getComposer()->getPackage()->getExtra();

        $symlinks = [];

        if ( isset( $options[$id] ) && is_array( $options[$id] ) )
            $symlinks = $options[$id];

        return $symlinks;
    }

    /**
     * Import folders and SyncManager to a specific destination according to remote.yml configuration.
     * BE CAREFUL WITH THIS FUNCTION
     *
*@param string $direction 'withdraw' | 'deploy'
     * @param string $env 'production' | 'staging'
     */
    public function SyncManagerync($direction, $env)
    {
        $this->loadConfig();
        $remote_cfg = $this->config->get($env . '.ssh');
        $port_option = '';

        // Assuming that all params are well written
        if ( !$remote_cfg || !isset($remote_cfg['host'], $remote_cfg['root_dir']))
        {
            $this->io->writeError('  ERROR : ' . $env . ' file is not complete, please check up your configuration.');
            return;
        }

        if (!isset($remote_cfg['rsync']))
        {
            $this->io->writeError('  ERROR : You must provide file names in rsync field.');
            return;
        }

        if (isset($remote_cfg['port']))
        {
            $port_option = "-p ".$remote_cfg['port']." ";
        }

        // Downloading each folder from source to destination
        foreach ($remote_cfg['rsync'] as $local_folder)
        {
            // user@domain.nom:/root_dir/path/to/dir/
            $distant_folder = $remote_cfg['host'] .':'. $remote_cfg['root_dir'] . $local_folder;

            $source = $distant_folder;
            $destination = $local_folder;

            // mkdir -p path/to/dir/
            $mkdir_command = 'mkdir -p '.$local_folder;

            // Deploying to a server is just the reverse process
            if ($direction == 'deploy')
            {
                $source = $local_folder;
                $destination = $distant_folder;

                // ssh user@domain.com (-p port) 'cd /root_dir/ && mkdir -p path/to/dir/'
                $mkdir_command = 'ssh '.$remote_cfg['host']." ".$port_option." 'cd ".$remote_cfg['root_dir']." && ".$mkdir_command."'";
            }

            $confirm = $this->io->askConfirmation("\n  Please confirm informations :".
                "\n  SERVER      : ".$source.
                "\n  DESTINATION : ".$destination.
                "\n  Continue ? [y,n] "
            );

            if (!$confirm)
            {
                $this->io->write('  Skipping file.');
                continue;
            }

            passthru($mkdir_command);

            $this->_rsync($source, $destination, $port_option);

            // ssh user@domain.com (-p port) 'cd /root_dir/ && mkdir -p path/to/dir/'
            $commands  = "sudo chown -R $(id -g):www-data ".$local_folder;
            $commands .= " && sudo find ".$local_folder." -type f -exec chmod 664 {} \\;";
            $commands .= " && sudo find ".$local_folder." -type d -exec chmod 775 {} \\;";

            // Deploying to a server is just the reverse process
            if ($direction == 'deploy')
            {
                $permission_command = 'ssh '.$remote_cfg['host']." ".$port_option." 'cd ".$remote_cfg['root_dir']." && ".$commands."'";
                passthru($permission_command);
            }
            else
            {
                passthru($commands);
            }
        }
        
    }

    public function databaseSync($action, $env)
    {
        $this->loadConfig();

        // retrieving remote.yml environment informations
        $remote_cfg = $this->config->get($env);

        // Assuming that all params are well written
        if ( !isset($remote_cfg['ssh']['host'], $remote_cfg['ssh']['root_dir']))
        {
            $this->io->writeError('  ERROR : ' . $env . ' file is not complete, please check up your configuration.'.$this->getComposerSyncDescription());
            return;
        }

        /** @var Database $db */
        $db = Database::getInstance($this->io);

        if (method_exists($db, $action)) {
            $db->$action($remote_cfg);
        }
    }

    private function _rsync($source, $destination, $port_option = '')
    {
        $port_option = empty($port_option)?"":" -e 'ssh ".$port_option."' ";

        passthru("rsync ".$port_option.
            "--recursive --human-readable --verbose --perms --times --compress --prune-empty-dirs ".
            "--force --delete-after --links --delete-excluded ".
            $source . " " .$destination);
    }


    /**
     * Retrieve configuration from app/config Yaml SyncManager
     *
*@return Data
     */
    public function loadConfig()
    {

        $data = [];

        include getcwd() . DIRECTORY_SEPARATOR . "vendor/mustangostang/spyc/Spyc.php";

        $config_path = getcwd() . DIRECTORY_SEPARATOR . "app/config";

        foreach ( [
                      'global',
                      'remote',
                      'local'
                  ] as $config ) {

            $file = $config_path . '/' . $config . '.yml';

            if ( file_exists( $file ) ) {
                $data = array_merge( $data, \Spyc::YAMLLoad( $file ) );
            }
        }

        $this->config = new Data( $data );
    }

    /**
     * @return Data
     */
    public function getConfig() {
        return $this->config;
    }

    /**
     * @return string
     */
    public function getComposerSyncDescription() {

        return  "\n  ------------------------ \n".
                "  COMPOSER SYNCHRONIZATION \n".
                "  ------------------------ \n".
                "  composer sync [action] [environment] [options1, ...]\n".
                "           [action]      : withdraw | deploy\n".
                "           [environment] : local | production | staging \n".
                "           [options] : only-db | only-file | force ( -f ) \n";
    }
}
