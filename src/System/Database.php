<?php
/**
 * User: Paul Coudeville <paul@metabolism.fr>
 */

namespace Rocket\System;

use Composer\Script\Event;
use Dflydev\DotAccessData\Data;
use Rocket\Application\SingletonTrait;

/**
 * Class Database
 *
 * @see     SingletonTrait
 * @package Rocket\System
 */
class Database {

    use SingletonTrait;

    /** @var Files $files */
    private $files;
    /** @var Event $event */
    private $event;
    /** @var IOInterface $io */
    private $io;
    /** @var Data $config */
    private $config;


    /**
     * Database constructor.
     *
     * @param Event $event
     */
    public function __construct(Event $event)
    {

        $this->files = new Files( $event );
        $this->event = $event;
        $this->io    = $event->getIO();

        $this->loadConfig();
    }


    /**
     * Retrieve configuration from app/config Yaml files
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

    public static function handle(Event $event)
    {
        /** @var Database $database */
        $database = Database::getInstance( $event );
        $args     = $event->getArguments();

        if ( !count( $args ) ) {

            $database->io->writeError( $database->getComposerDatabaseDescription() );

            return;
        }

        $action = $args[0];

        if ( $action != 'import' && $action != 'export' ) {

            $database->io->writeError( "  Wrong action call\n" . "  action can be 'import' or 'export' only." . $database->getComposerDatabaseDescription() );

            return;
        }

        if ( $action == 'import' && count( $args ) < 2 ) {

            $database->io->writeError( "  Missing path argument\n" . $database->getComposerDatabaseDescription() );

            return;
        }

        $archive_info = $args[1];

        try {

            $database->prepare( $action, $archive_info );

        } catch ( \Exception $e ) {

            $database->io->write( "  ERROR : " . $e->getMessage() );
        }
    }

    /**
     * @param $action
     * @param $archive_info
     */
    public function prepare($action, $archive_info)
    {

        if ( !method_exists( $this, $action ) ) {

            throw new \InvalidArgumentException( $action . " function does not exist !" . $this->getComposerDatabaseDescription() );
        }

        $db_infos = [];
        // Common configuration
        $db_infos['user']     = $this->config->get( 'database.user' );
        $db_infos['password'] = $this->config->get( 'database.password' );
        $db_infos['name']     = $this->config->get( 'database.name' );
        $db_infos['host']     = "localhost";

        $this->io->write( '  Starting ' . $action . '...' );


        return $this->$action( $archive_info, $db_infos );
    }


    /**
     * Process Withdrawing from remote configuration to local
     *
     * @param $remote_cfg
     */
    public function withdraw($remote_cfg)
    {
        $tmp_dir = 'var';

        $replacements = isset( $remote_cfg['database']['replace'] ) ? $remote_cfg['database']['replace'] : false;

        // Creating local informations
        $local_cfg = $this->config->get('database');

        if( !isset($local_cfg['host']) )
            $local_cfg['host'] = "localhost";

        $exported_db = $this->export( $tmp_dir, $remote_cfg['database'], $replacements );

        $this->import( $exported_db, $local_cfg );

        unlink( $exported_db );
    }


    /**
     * Process deployment of local configuration to given remote configuration
     *
     * @param $remote_cfg
     */
    public function deploy($remote_cfg)
    {
        $tmp_dir = 'var';

        if ( isset( $remote_cfg['database']['replace'] ) )
        {
            // deployment is the reverse replacement array
            $replacements = [];
            foreach ( $remote_cfg['database']['replace'] as $replacement )
            {
                foreach ( $replacement as $new => $old )
                {
                    $replacements[] = [$old => $new];
                }
            }
        }
        else
        {
            $replacements = false;
        }

        // Creating local informations
        $local_cfg = $this->config->get('database');

        if( !isset($local_cfg['host']) )
            $local_cfg['host'] = "localhost";

        $exported_db = $this->export( $tmp_dir, $local_cfg, $replacements );

        $this->import( $exported_db, $remote_cfg['database'] );

        unlink( $exported_db );
    }


    /**
     * Import database
     */
    public function import($archive_path, $db_infos)
    {
        if ( !is_array( $db_infos ) || !isset( $db_infos['user'], $db_infos['password'], $db_infos['name'], $db_infos['host'] ) )
        {
            throw new \InvalidArgumentException( "Database informations are not complete." );
        }

        if ( file_exists( $archive_path ) )
        {
            $confirm = $this->io->askConfirmation( "  File name : " . $archive_path . "\n  Confirm import ? [y,n] ", false );

            if ( $confirm )
            {
                $file_info = pathinfo( $archive_path );

                $command = "";

                // extracting file content
                if ( $file_info['extension'] == 'gz' )
                {
                    $command .= "zcat " . $archive_path . " | ";
                }

                $command .= "mysql -u " . $db_infos['user'] . " -h " . $db_infos['host'] . " --password='" . $db_infos['password'] . "' " . $db_infos['name'];

                passthru( $command );

                $this->io->write( "\n  Import complete." );
            }
        }
        else {

            throw new \InvalidArgumentException( $archive_path . ' does not exists.' );
        }
    }


    /**
     * Export database
     *
     * @param $export_dir string path to exported database
     * @param $db_infos   array database infos
     * @param bool $replacements
     * @return string filename
     */
    public function export($export_dir, $db_infos, $replacements = false)
    {
        if ( !$export_dir )
        {
            $backup_path = getcwd() . DIRECTORY_SEPARATOR . "app/resources/db";
        }
        else
        {
            $backup_path = getcwd() . DIRECTORY_SEPARATOR . $export_dir;
        }

        if ( !is_dir( $backup_path ) ) {
            mkdir( $backup_path );
        }

        $filename = $backup_path . '/' . date( 'Ymd' ) . '.sql.gz';

        $this->io->write( '  Exporting database...' );
        $dump        = "mysqldump -u " . $db_infos['user'] . " --password='" . $db_infos['password'] . "' -h " . $db_infos['host'] . " " . $db_infos['name'];
        $compression = "gzip > " . $filename;
        $replace     = ( $replacements ? ' | ' . $this->getReplace( $replacements ) : '' );

        $command = $dump . $replace . ' | ' . $compression;

        passthru( $command );

        $this->io->write( "  Exporting successful !\n" . "  Path to export : " . $filename );

        return $filename;
    }


    /**
     * Replace database strings to new strings
     *
     * @param $replacements array
     * @return string thh command
     */
    private function getReplace($replacements)
    {
        $command = '';

        if ( isset( $replacements ) )
        {
            $command = "sed '";
            foreach ( $replacements as $replacement )
            {
                foreach ( $replacement as $old => $new )
                {
                    $command .= "s," . $old . "," . $new . ",g; ";
                }
            }
            $command .= "' ";
        }

        return $command;
    }


    /**
     * @return string
     */
    public function getComposerDatabaseDescription()
    {
        return "\n  ----------------- \n" . "  COMPOSER DATABASE \n" . "  ----------------- \n" . "  composer database [action] [path]\n" . "           [action] : \n" . "             - import\n" . "             - export\n" . "           [path] : relative path to directory or database\n";
    }
}
