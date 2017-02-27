<?php

namespace Rocket\System;

use Composer\Script\Event;
use Composer\Util\Filesystem;
use Dflydev\DotAccessData\Data;
use Rocket\Application\SingletonTrait;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

/**
 * Class Files
 *
 * File Manager
 *
 * @package Rocket\System
 */
class Files {

    use SingletonTrait;

    /**
     * @var Event $event
     * @var IOInterface $io
     * @var Data $config
     */
    private $event, $io, $config;

    /**
     * Files constructor.
     *
     * @param Event $event
     */
    public function __construct(Event $event)
    {

        $this->event = $event;
        $this->io    = $event->getIO();
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
     * File Copy
     *
     * @param Event $event
     */
    public function copy(Event $event)
    {
        $files = $this->get( $event, 'copy-file' );

        $finder = new Finder;
        $fs     = new Filesystem();
        $sfs    = new \Symfony\Component\Filesystem\Filesystem();
        $io     = $event->getIO();

        foreach ( $event->getComposer()->getRepositoryManager()->getLocalRepository()->getPackages() as $package )
        {
            if ( isset( $files[$package->getName()] ) )
            {
                $packageDir = $event->getComposer()->getInstallationManager()->getInstallPath( $package );

                $filesDefinitions = $files[$package->getName()];

                foreach ( $filesDefinitions as $from => $to )
                {
                    if ( $fs->isAbsolutePath( $from ) )
                    {
                        throw new \InvalidArgumentException( "Invalid target path '$from' for package'{$package->getName()}'." . ' It must be relative.' );
                    }

                    if ( $fs->isAbsolutePath( $to ) )
                    {
                        throw new \InvalidArgumentException( "Invalid link path '$to' for package'{$package->getName()}'." . ' It must be relative.' );
                    }

                    $from = $packageDir . DIRECTORY_SEPARATOR . $from;
                    $to   = getcwd() . DIRECTORY_SEPARATOR . $to;

                    $fs->ensureDirectoryExists( dirname( $to ) );

                    if ( is_dir( $from ) )
                    {
                        $finder->files()->in( $from );

                        foreach ( $finder as $file )
                        {
                            $dest = sprintf( '%s/%s', $to, $file->getRelativePathname() );

                            try
                            {
                                if ( file_exists( $dest ) )
                                {
                                    $fs->unlink( $dest );
                                }

                                $sfs->copy( $file, $dest );

                            } catch ( IOException $e )
                            {
                                throw new \InvalidArgumentException( sprintf( '<error>Could not copy %s</error>', $file->getBaseName() ) );
                            }
                        }
                    }
                    else
                    {
                        try
                        {

                            if ( file_exists( $to ) )
                                $fs->unlink( $to );

                            $sfs->copy( $from, $to );

                            $io->write( sprintf( '  Copying <comment>%s</comment> to <comment>%s</comment>.', str_replace( getcwd(), '', $from ), str_replace( getcwd(), '', $to ) ) );

                        }
                        catch ( IOException $e )
                        {
                            throw new \InvalidArgumentException( sprintf( '<error>Could not copy %s</error>', $from ) );
                        }
                    }
                }
            }
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
     * Folder removal
     *
     * @param Event $event
     */
    public function remove(Event $event)
    {
        $files = $this->get( $event, 'remove-file' );

        $fs  = new Filesystem();
        $io  = $event->getIO();

        foreach ( $event->getComposer()->getRepositoryManager()->getLocalRepository()->getPackages() as $package )
        {
            if ( isset( $files[$package->getName()] ) )
            {
                $filesDefinitions = $files[$package->getName()];

                foreach ( $filesDefinitions as $file )
                {
                    if ( $fs->isAbsolutePath( $file ) )
                    {
                        throw new \InvalidArgumentException( "Invalid target path '$file' for package'{$package->getName()}'." . ' It must be relative.' );
                    }

                    $file = getcwd() . DIRECTORY_SEPARATOR . $file;

                    try
                    {
                        if ( is_dir( $file ) )
                        {
                            $fs->removeDirectory( $file );
                            $io->write( sprintf( '  Removing directory <comment>%s</comment>.', str_replace( getcwd(), '', $file ) ) );
                        }
                        elseif ( file_exists( $file ) )
                        {
                            $fs->unlink( $file );
                            $io->write( sprintf( '  Removing file <comment>%s</comment>.', str_replace( getcwd(), '', $file ) ) );
                        }


                    } catch ( IOException $e )
                    {
                        throw new \InvalidArgumentException( sprintf( '<error>Could not remove %s</error>', $file ) );
                    }
                }
            }
        }
    }

    /**
     * Folder Creation
     *
     * @param Event $event
     */
    public function createFolder(Event $event)
    {
        $files = $this->get( $event, 'create-folder' );

        $fs  = new Filesystem();
        $io  = $event->getIO();

        foreach ( $event->getComposer()->getRepositoryManager()->getLocalRepository()->getPackages() as $package ) {

            if ( isset( $files[$package->getName()] ) ) {

                $filesDefinitions = $files[$package->getName()];

                foreach ( $filesDefinitions as $file => $permissions ) {

                    if ( $fs->isAbsolutePath( $file ) ) {

                        throw new \InvalidArgumentException( "Invalid target path '$file' for package'{$package->getName()}'." . ' It must be relative.' );
                    }

                    $file = getcwd() . DIRECTORY_SEPARATOR . $file;

                    try {

                        if ( !is_dir( $file ) && !file_exists( $file ) ) {

                            $io->write( sprintf( '  Creating directory <comment>%s</comment>.', str_replace( getcwd(), '', $file ) ) );

                            $oldmask = umask( 0 );
                            mkdir( $file, octdec( $permissions ) );
                            umask( $oldmask );
                        }
                    } catch ( IOException $e ) {

                        throw new \InvalidArgumentException( sprintf( '<error>Could not create %s</error>', $e ) );
                    }
                }
            }
        }
    }


    /**
     * Synchronize files
     * @param Event $event
     */
    public static function sync(Event $event)
    {
        /** @var Files $files */
        $files  = Files::getInstance( $event );
        $args   = $event->getArguments();

        // Arguments checking
        if ( count( $args ) < 2)
        {
            $files->io->writeError( "  Not enough argument\n".
            $files->getComposerSyncDescription());

            return;
        }

        $action = $args[0];
        $env    = $args[1];
        $options = isset($args[2])?array_slice($args, 2): false;

        $confirmed = true;

        if ($action == 'deploy')
        {
            $files->loadConfig();
            $current_env = $files->getConfig()->get('environment');

            // Preventing mistakes
            if ($current_env == 'local' && $env == 'production' && !(isset($options) && is_array($options) && in_array('force', $options)))
            {
                $files->io->writeError("  ERROR: We are very sorry but you cannot deploy to production from a local environment. \n  If you really want to, try force or -f option".$files->getComposerSyncDescription());
                return;
            }

            $confirmed = $files->io->askConfirmation( '  Please note that this will override current content in distant server. Continue ? [y,n] ', false);

            if ($confirmed)
                $confirmed = $files->io->askConfirmation( '  C\'mon.. Really ? [y,n] ', false);

        }
        elseif ($action != 'withdraw')
        {
            $files->io->writeError( "  Wrong action call\n".
                "  action can be 'withdraw' or 'deploy' only.".$files->getComposerSyncDescription());

            return;
        }


        // Starting process
        if ($confirmed)
        {
            if (!$options || in_array('only-file', $options))
            {
                // Starting Sync
                $files->fileSync( $action, $env );
            }

            if (!$options || in_array('only-database', $options))
            {
                // Starting Database Import
                $files->databaseSync( $action, $env );
            }

            return;
        }

        $files->io->write( '  Abording process.' );

    }


    /**
     * Import folders and files to a specific destination according to remote.yml configuration.
     * BE CAREFUL WITH THIS FUNCTION
     * @param string $direction 'withdraw' | 'deploy'
     * @param string $env 'production' | 'staging'
     */
    public function fileSync($direction, $env)
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
        $db = Database::getInstance($this->event);

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
     * Retrieve configuration from app/config Yaml files
     * @return Data
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
