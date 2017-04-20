<?php
/**
 * User: Paul Coudeville <paul@metabolism.fr>
 */

namespace Rocket\Composer\Command;


use Composer\Command\BaseCommand;
use Rocket\System\SyncManager;
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
    Perform specific deployment and withdrawal action with remote machines. \n
    This commands must use rocket boilerplate framework.
EOT
        );

        $this->setDescription("CI - CD for Metabolism boilerplate");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var SyncManager $SyncManager */
        $SyncManager = SyncManager::getInstance( $this->getIO() );
        $args   = $input->getArgument('actions');

        // Arguments checking
        if ( count( $args ) < 2)
        {
            $this->getIO()->writeError( "  Not enough argument\n". $SyncManager->getComposerSyncDescription() );

            return;
        }

        $action = $args[0];
        $env    = $args[1];
        $options = isset($args[2])?array_slice($args, 2): false;

        $confirmed = true;

        if ($action == 'deploy')
        {
            $SyncManager->loadConfig();
            $current_env = $SyncManager->getConfig()->get( 'environment' );

            // Preventing mistakes
            if ($current_env == 'local' && $env == 'production' && !(isset($options) && is_array($options) && in_array('force', $options)))
            {
                $this->getIO()
                     ->writeError( "  ERROR: We are very sorry but you cannot deploy to production from a local environment. \n  If you really want to, try force or -f option" . $SyncManager->getComposerSyncDescription() );
                return;
            }

            $confirmed = $this->getIO()->askConfirmation( '  Please note that this will override current content in distant server. Continue ? [y,n] ', false);

            if ($confirmed)
                $confirmed = $this->getIO()->askConfirmation( '  C\'mon.. Really ? [y,n] ', false);

        }
        elseif ($action != 'withdraw')
        {
            $this->getIO()->writeError( "  Wrong action call\n". "  action can be 'withdraw' or 'deploy' only." . $SyncManager->getComposerSyncDescription() );

            return;
        }


        // Starting process
        if ($confirmed)
        {
            if (!$options || in_array('only-file', $options))
            {
                // Starting Sync
                $SyncManager->SyncManagerync( $action, $env );
            }

            if (!$options || in_array('only-database', $options))
            {
                // Starting Database Import
                $SyncManager->databaseSync( $action, $env );
            }

            return;
        }

        $this->getIO()->write( '  Abording process.' );

    }
}
