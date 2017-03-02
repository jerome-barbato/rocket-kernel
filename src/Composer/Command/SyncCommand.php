<?php
/**
 * User: Paul Coudeville <paul@metabolism.fr>
 */

namespace Rocket\Composer\Command;


use Composer\Command\BaseCommand;
use Rocket\System\FileManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SyncCommand extends BaseCommand
{
    protected function configure()
    {
        $this->setName('sync');
        $this->addArgument('actions', InputArgument::IS_ARRAY, "Modules");
        $this->setHelp(<<<EOT

EOT
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var FileManager $FileManager */
        $FileManager  = FileManager::getInstance( $this->getIO() );
        $args   = $input->getArgument('actions');

        // Arguments checking
        if ( count( $args ) < 2)
        {
            $FileManager->io->writeError( "  Not enough argument\n".
                $FileManager->getComposerSyncDescription());

            return;
        }

        $action = $args[0];
        $env    = $args[1];
        $options = isset($args[2])?array_slice($args, 2): false;

        $confirmed = true;

        if ($action == 'deploy')
        {
            $FileManager->loadConfig();
            $current_env = $FileManager->getConfig()->get('environment');

            // Preventing mistakes
            if ($current_env == 'local' && $env == 'production' && !(isset($options) && is_array($options) && in_array('force', $options)))
            {
                $FileManager->io->writeError("  ERROR: We are very sorry but you cannot deploy to production from a local environment. \n  If you really want to, try force or -f option".$FileManager->getComposerSyncDescription());
                return;
            }

            $confirmed = $FileManager->io->askConfirmation( '  Please note that this will override current content in distant server. Continue ? [y,n] ', false);

            if ($confirmed)
                $confirmed = $FileManager->io->askConfirmation( '  C\'mon.. Really ? [y,n] ', false);

        }
        elseif ($action != 'withdraw')
        {
            $FileManager->io->writeError( "  Wrong action call\n".
                "  action can be 'withdraw' or 'deploy' only.".$FileManager->getComposerSyncDescription());

            return;
        }


        // Starting process
        if ($confirmed)
        {
            if (!$options || in_array('only-file', $options))
            {
                // Starting Sync
                $FileManager->FileManagerync( $action, $env );
            }

            if (!$options || in_array('only-database', $options))
            {
                // Starting Database Import
                $FileManager->databaseSync( $action, $env );
            }

            return;
        }

        $FileManager->io->write( '  Abording process.' );

    }
}
