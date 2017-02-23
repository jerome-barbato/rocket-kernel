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

        $this->files = new Files($event);
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
        $args   = $event->getArguments();

        if ( !count($args)) {

            $database->io->writeError($database->getComposerDatabaseDescription());

            return;
        }

        $action = $args[0];

        if ($action != 'import' && $action != 'export') {

            $database->io->writeError( "  Wrong action call\n".
                "  action can be 'import' or 'export' only.".$database->getComposerDatabaseDescription());
            return;
        }

        if ($action == 'import' && count($args) < 2) {

            $database->io->writeError( "  Missing path argument\n".$database->getComposerDatabaseDescription());
            return;
        }

        $archive_info = $args[1];

        try {

            $database->prepare($action, $archive_info);

        } catch (\Exception $e) {

            $database->io->write("  ERROR : ".$e->getMessage());
        }
    }

    /**
     * @param $action
     * @param $archive_info
     */
    public function prepare($action, $archive_info)
    {


        if (!method_exists($this, $action)) {

            throw new \InvalidArgumentException( $action . " function does not exist !" . $this->getComposerDatabaseDescription());
        }

        $db_infos = [];
        // Common configuration
        $db_infos['username']   = $this->config->get( 'database.user' );
        $db_infos['password']   = $this->config->get( 'database.password' );
        $db_infos['db_name']    = $this->config->get( 'database.name' );
        $db_infos['host']       = "localhost";

        $this->io->write( '  Starting '.$action.'...' );
        $this->$action($archive_info, $db_infos);
    }

    public function withdraw($remote_cfg) {

    }

    /**
     * Import database
     */
    public function import($archive_path, $db_infos)
    {

        if (!is_array($db_infos) || !isset($db_infos['username'], $db_infos['password'], $db_infos['db_name'], $db_infos['host'])) {
            throw new \InvalidArgumentException("Database informations are not compelete.");
        }

        if ( file_exists( $archive_path ) ) {

            $confirm = $this->io->askConfirmation("  File name : ".getcwd().DIRECTORY_SEPARATOR.$archive_path."\n  Confirm import ? [y,n] ", false);

            if($confirm) {

                $file_info = pathinfo( $archive_path );

                $command = "";

                // extracting file content
                if ($file_info['extension'] == 'gz') {
                    $command .= "zcat ".$archive_path." | ";
                }
                $command .= "mysql -u " . $db_infos['username']. " -h " . $db_infos['host']. " --password='" . $db_infos['password']. "' " . $db_infos['db_name'];

                passthru($command);

                $this->io->write( "\n  Import complete." );
            }


        }
        else {

            throw new \InvalidArgumentException($archive_path . ' does not exists.' );
        }
    }


    private function getReplace($action, $env) {

        $replacements = $this->config->get( $env . '.database.replace' );
        var_dump($env . '.database.replace');

        if (isset($replacements) ) {

            $command = "sed '";
            var_dump($replacements);
            foreach ($replacements as $replacement) {

            }
        }

        $command = "sed 's,http://localhost/corporate/wp,http://www.replace.com/page/,g' sample.sql";
        return $command;
    }


    /**
     * Export database
     */
    public function export($archive_name)
    {

        $backup_path = getcwd() . DIRECTORY_SEPARATOR . "app/resources/db";

        if ( !is_dir( $backup_path ) ) {
            mkdir( $backup_path );
        }

        $filename = $backup_path . '/' . date( 'Ymd' ) . '.sql.gz';

        $this->io->write( '  Exporting database...' );
        passthru( "mysqldump -u " . $this->config->get( 'database.user' ) . " --password='" . $this->config->get( 'database.password' ) . "' " . $this->config->get( 'database.name' ) . " | gzip > " . $filename );

        $this->io->write( '  Exporting complete' );
    }


    public function getComposerDatabaseDescription() {

        return  "\n  ----------------- \n".
            "  COMPOSER DATABASE \n".
            "  ----------------- \n".
            "  composer database [action] [param]\n".
            "           [action] : \n".
            "             - import [path]\n".
            "             - export [name]\n";
    }
}
