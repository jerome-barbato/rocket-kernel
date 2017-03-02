<?php
/**
 * User: Paul Coudeville <paul@metabolism.fr>
 */

namespace Rocket\Composer\Command;

use Composer\Command\BaseCommand;
use Composer\IO\IOInterface;
use Rocket\System\Database;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DatabaseCommand extends BaseCommand
{
    protected function configure()
    {
        $this->setName('database');
        $this->addArgument('actions', InputArgument::IS_ARRAY, "Actions");
        $this->setHelp(<<<EOT
Database actions
EOT
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        /** @var Database $database */
        $database = Database::getInstance( $this->getIO() );
        $args     = $input->getArgument('actions');

        if ( !count( $args ) ) {

            $output->write( $database->getComposerDatabaseDescription() );

            return;
        }

        $action = $args[0];

        if ( $action != 'import' && $action != 'export' ) {

            $output->write( "  Wrong action call\n" . "  action can be 'import' or 'export' only." . $database->getComposerDatabaseDescription() );

            return;
        }

        if ( $action == 'import' && count( $args ) < 2 ) {

            $output->write( "  Missing path argument\n" . $database->getComposerDatabaseDescription() );

            return;
        }

        $archive_info = $args[1];

        try {

            $database->prepare( $action, $archive_info );

        } catch ( \Exception $e ) {

            $output->write( "  ERROR : " . $e->getMessage() );
        }
    }
}
