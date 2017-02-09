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

    private $files, $event, $io, $config;


    /**
     * Database constructor.
     *
     * @param Event $event
     */
    public function __construct(Event $event)
    {

        $this->files = new Files();
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

        foreach ( ['global', 'local'] as $config ) {

            $file = $config_path . '/' . $config . '.yml';

            if ( file_exists( $file ) ) {
                $data = array_merge( $data, \Spyc::YAMLLoad( $file ) );
            }
        }

        $this->config = new Data( $data );
    }


    /**
     * Import database
     */
    public static function import(Event $event)
    {

        $app_path = getcwd() . DIRECTORY_SEPARATOR . "app";
        $database = Database::getInstance( $event );
        $args     = $event->getArguments();

        if ( count( $args ) ) {
            $filename = $app_path . '/backup/' . $args[0];
        }
        else {
            $filename = $app_path . '/resources/db.sql';
        }

        if ( file_exists( $filename ) ) {

            $database->io->write( '  Importing database...' );

            if ( count( $args ) == 3 ) {

                file_put_contents( $filename . '.tmp', str_replace( $args[1], $args[2], file_get_contents( $filename ) ) );
                $filename = $filename . '.tmp';
            }

            passthru( "mysql -u " . $database->config->get( 'database.user' ) . " -p" . $database->config->get( 'database.password' ) . " " . $database->config->get( 'database.name' ) . " < " . $filename . "  2>&1 | grep -v \"Warning: Using a password\"" );
            $database->io->write( '  Import complete' );

            if ( count( $args ) == 3 ) {
                unlink( $filename );
            }
        }
        else {

            $database->io->write( '  ' . $filename . ' does not exists' );
        }
    }


    /**
     * Export database
     */
    public static function export(Event $event)
    {

        $database    = Database::getInstance( $event );
        $backup_path = getcwd() . DIRECTORY_SEPARATOR . "app/backup";
        $args        = $event->getArguments();

        if ( !is_dir( $backup_path ) ) {
            mkdir( $backup_path );
        }

        $filename = $backup_path . '/' . date( 'Ymd' ) . '.sql';

        $database->io->write( '  Exporting database...' );
        passthru( "mysqldump -u " . $database->config->get( 'database.user' ) . " -p" . $database->config->get( 'database.password' ) . " " . $database->config->get( 'database.name' ) . " > " . $filename );

        if ( count( $args ) == 2 ) {
            file_put_contents( $filename, str_replace( $args[0], $args[1], file_get_contents( $filename ) ) );
        }

        $database->io->write( '  Exporting complete' );
    }
}
